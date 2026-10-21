<?php

namespace App\Support\Settings;

use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;
use InvalidArgumentException;

final class CpanelGateway
{
    // Small fixed read-only UAPI allowlist. Write operations require their
    // own future reviewed methods; a plugin cannot choose arbitrary endpoints.
    public const OPERATIONS = [
        'email.accounts.list' => ['email.read', 'Email', 'list_pops'],
        'ssl.hosts.list' => ['ssl.read', 'SSL', 'list_installed_hosts'],
        'files.directory.list' => ['files.read', 'Fileman', 'list_files'],
        'backup.archives.list' => ['backup.read', 'Backup', 'list_backups'],
    ];

    public function __construct(
        private readonly CoreSettings $settings,
        private readonly CpanelPermissions $permissions
    ) {}

    /** @return array<string,mixed> */
    public function call(string $pluginSlug, string $operation, array $params = []): array
    {
        $definition = self::OPERATIONS[$operation] ?? null;
        if ($definition === null) {
            throw new InvalidArgumentException('Unsupported cPanel operation.');
        }
        [$capability, $module, $function] = $definition;
        if (! $this->permissions->can($pluginSlug, $capability)) {
            throw new AuthorizationException('This plugin does not have the required cPanel permission.');
        }

        // No generic arbitrary API parameters. A file listing can only reach
        // a path below public_html; no traversal or arbitrary server files.
        if ($operation === 'files.directory.list') {
            $dir = $params['dir'] ?? 'public_html';
            if (! is_string($dir) || strlen($dir) > 240
                || ! preg_match('#^public_html(?:/[A-Za-z0-9_-]+)*$#D', $dir)
                || array_diff(array_keys($params), ['dir'])) {
                throw new InvalidArgumentException('Unsupported file path or parameters.');
            }
            $params = ['dir' => $dir];
        } elseif ($params !== []) {
            throw new InvalidArgumentException('This operation does not accept parameters.');
        }

        $credential = $this->settings->cpanelCredentialForGateway();
        if ($credential === null) {
            throw new AuthorizationException('cPanel integration is disabled.');
        }

        // Re-resolve DNS for every request, forbid private and reserved IPs,
        // and PIN the chosen public address to curl's TLS-verified connection.
        $ips = gethostbynamel($credential['host']) ?: [];
        if ($ips === [] || ! defined('CURLOPT_RESOLVE')) {
            throw new RuntimeException('Unable to validate cPanel network destination.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
                | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('cPanel server resolves to an unsafe address.');
            }
        }
        $ip = $ips[0];

        $response = Http::withOptions([
            'allow_redirects' => false,
            'timeout' => 15,
            'connect_timeout' => 5,
            'verify' => true,
            'curl' => [CURLOPT_RESOLVE => [
                $credential['host'].':2083:'.$ip,
            ]],
        ])->withHeaders([
            'Authorization' => 'cpanel '.$credential['username'].':'.$credential['token'],
            'Accept' => 'application/json',
        ])->get(
            'https://'.$credential['host'].':2083/execute/'.$module.'/'.$function,
            $params
        );

        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('The cPanel UAPI request was unsuccessful.');
        }
        return $response->json();
    }
}
