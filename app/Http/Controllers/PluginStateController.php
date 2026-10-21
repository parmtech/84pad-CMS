<?php
namespace App\Http\Controllers;

use App\Support\Plugins\PluginRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PluginStateController extends Controller
{
    public function __invoke(Request $request, string $slug, PluginRegistry $registry): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate(['action' => ['required', Rule::in(['activate','deactivate'])]]);
        try {
            $registry->setEnabled($slug, $data['action'] === 'activate');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['plugin' => 'Plugin not found or incompatible.']);
        }
        return back()->with('notice', 'Plugin marker saved. This development version does not execute plugin PHP.');
    }
}
