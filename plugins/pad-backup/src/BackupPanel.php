<?php
declare(strict_types=1);

namespace Pad\Backup;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class BackupPanel
{
    public function __construct(private BackupService $service) {}

    public function handle(Request $request, string $action): Response
    {
        abort_unless($request->user()?->isOwner(), 403);
        View::addNamespace('padbackup', dirname(__DIR__).'/resources/views');

        if ($request->isMethod('GET') && $action === 'index') {
            return response()->view('padbackup::dashboard', [
                'settings' => $this->service->settings(),
                'backups' => $this->service->list(),
            ]);
        }
        if ($request->isMethod('GET') && $action === 'export') {
            abort_unless($request->isSecure(), 403, 'Use HTTPS to export credential-bearing backup ZIPs.');
            $id = (string) $request->query('id', '');
            try {
                $file = $this->service->portableExport($id);
                return response()->download($file, '84pad-'.$id.'.zip', [
                    'Cache-Control' => 'no-store, private',
                    'X-Content-Type-Options' => 'nosniff',
                ])->deleteFileAfterSend(true);
            } catch (Throwable $e) {
                Log::warning('84PAD portable backup export failed');
                return redirect('/dashboard/extensions/pad-backup')->with('backup_error', 'Export failed. Check backup integrity and APP_KEY.');
            }
        }
        abort_unless($request->isMethod('POST'), 405);
        try {
            if ($action === 'settings') {
                $data = $request->validate(['frequency' => 'required|in:off,hourly,daily,weekly,monthly']);
                $this->service->configure($data['frequency']);
                $message = 'Automatic backup schedule saved.';
            } elseif ($action === 'run') {
                $this->service->backup();
                $message = 'Encrypted full-site backup created and verified.';
            } elseif ($action === 'import') {
                $data = $request->validate(['archive' => 'required|file|max:2097152']);
                $this->service->portableImport($data['archive']);
                $message = 'Portable ZIP verified and imported. Restore is not enabled in this preview.';
            } elseif ($action === 'verify') {
                $id = $request->validate(['id' => 'required|regex:/^b[0-9]{14}-[a-f0-9]{16}$/D'])['id'];
                if (!$this->service->verify($id)) throw new \RuntimeException('Verification failed.');
                $message = 'Backup checksums and authenticated encryption verified.';
            } elseif ($action === 'delete') {
                $id = $request->validate(['id' => 'required|regex:/^b[0-9]{14}-[a-f0-9]{16}$/D'])['id'];
                $this->service->delete($id);
                $message = 'Selected backup deleted.';
            } else {
                abort(404);
            }
            return redirect('/dashboard/extensions/pad-backup')->with('backup_notice', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('84PAD backup operation failed', ['action' => $action]);
            return redirect('/dashboard/extensions/pad-backup')
                ->with('backup_error', 'Backup action failed. Check storage space, PHP upload limits, database permissions and logs.');
        }
    }
}
