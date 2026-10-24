<?php
declare(strict_types=1);

namespace App\Support\Installer;

use RuntimeException;

/**
 * Only called from the private, one-time 84PAD web bootstrap.
 * cPanel credentials are never persisted; the Owner may separately connect
 * the encrypted cPanel gateway after installation.
 */
final class CpanelDatabaseBootstrap
{
    /** @return array{db_host:string,db_port:string,db_name:string,db_user:string,db_password:string} */
    public static function provision(array $input): array
    {
        $host = trim((string) ($input['cpanel_host'] ?? ''));
        $account = trim((string) ($input['cpanel_username'] ?? ''));
        $token = (string) ($input['cpanel_token'] ?? '');
        $dbSuffix = trim((string) ($input['db_suffix'] ?? ''));
        $userSuffix = trim((string) ($input['db_user_suffix'] ?? ''));
        $password = (string) ($input['db_password'] ?? '');

        if (! preg_match('/^[a-z0-9][a-z0-9.-]{0,251}[a-z0-9]$/D', $host)
            || ! preg_match('/^[a-z][a-z0-9]{0,15}$/D', $account)
            || ! preg_match('/^[a-z0-9_]{1,12}$/D', $dbSuffix)
            || ! preg_match('/^[a-z0-9_]{1,12}$/D', $userSuffix)
            || strlen($token) < 16 || strlen($token) > 512 || preg_match('/[\x00-\x20]/', $token)
            || strlen($password) < 16 || strlen($password) > 256
            || preg_match('/[\r\n\x00]/', $password)) {
            throw new RuntimeException('Check cPanel host, username, API token and new database credentials.');
        }

        $database = $account.'_'.$dbSuffix;
        $user = $account.'_'.$userSuffix;
        if (strlen($database) > 64 || strlen($user) > 32) {
            throw new RuntimeException('The cPanel account prefix and database suffix are too long.');
        }

        // Require verified public DNS, then pin the resolved address to the
        // cURL connection: no user-controlled internal/metadata URL requests.
        if (! function_exists('curl_init') || ! defined('CURLOPT_RESOLVE')) {
            throw new RuntimeException('This hosting plan does not provide the HTTPS cPanel connector; use manual setup.');
        }
        $ips = gethostbynamel($host);
        if (! is_array($ips) || $ips === []) {
            throw new RuntimeException('Cannot resolve the cPanel server hostname.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('The cPanel hostname must resolve to a public server address.');
            }
        }
        $ip = $ips[0];

        // Do not clean up arbitrary existing databases after a failure; the
        // account owner should inspect partial resources in cPanel instead.
        self::request($host, $ip, $account, $token, 'Mysql/create_database', ['name' => $database]);
        self::request($host, $ip, $account, $token, 'Mysql/create_user', [
            'name' => $user, 'password' => $password,
        ]);
        self::request($host, $ip, $account, $token, 'Mysql/set_privileges_on_database', [
            'database' => $database, 'user' => $user, 'privileges' => 'ALL PRIVILEGES',
        ]);

        return [
            'db_host' => trim((string) ($input['db_host'] ?? 'localhost')),
            'db_port' => '3306',
            'db_name' => $database,
            'db_user' => $user,
            'db_password' => $password,
        ];
    }

    /** @param array<string,string> $parameters */
    private static function request(
        string $host,
        string $ip,
        string $username,
        string $token,
        string $operation,
        array $parameters
    ): void {
        // The operation is not user-controllable; no generic API proxy.
        if (! in_array($operation, [
            'Mysql/create_database',
            'Mysql/create_user',
            'Mysql/set_privileges_on_database',
        ], true)) {
            throw new RuntimeException('Unsupported setup operation.');
        }

        $curl = curl_init();
        if ($curl === false) {
            throw new RuntimeException('Unable to connect to cPanel.');
        }
        $body = '';
        try {
            $options = [
                CURLOPT_URL => 'https://'.$host.':2083/execute/'.$operation,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($parameters, '', '&', PHP_QUERY_RFC3986),
                CURLOPT_HTTPHEADER => [
                    'Authorization: cpanel '.$username.':'.$token,
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_RESOLVE => [$host.':2083:'.$ip],
                CURLOPT_PROXY => '',
                CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$body): int {
                    if (strlen($body) + strlen($chunk) > 131072) {
                        return 0;
                    }
                    $body .= $chunk;
                    return strlen($chunk);
                },
            ];
            if (defined('CURLOPT_PROTOCOLS_STR')) {
                $options[constant('CURLOPT_PROTOCOLS_STR')] = 'https';
            } else {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            curl_setopt_array($curl, $options);
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($curl);
        }

        $decoded = json_decode($body, true);
        if ($ok !== true || $status !== 200 || ! is_array($decoded)
            || ! isset($decoded['result']) || ! is_array($decoded['result'])
            || ($decoded['result']['status'] ?? 0) !== 1
            || ! empty($decoded['result']['errors'])) {
            // Never print the remote body, since hosts may echo arguments.
            throw new RuntimeException(
                'cPanel could not complete database provisioning. Check the account, available database slots and permissions. '.
                'Partial resources may exist; inspect MySQL Databases before retrying.'
            );
        }
    }
}
