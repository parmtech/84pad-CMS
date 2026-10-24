<?php

return [
    'core_version' => '0.1.0',
    // 128 MiB ZIP, 512 MiB extracted; shared hosting PHP upload settings must match.
    'plugin_zip_max_bytes' => 134217728,
    'plugin_extracted_max_bytes' => 536870912,
    'plugin_zip_max_entries' => 5000,
    // Generated automatically by the secure hosting bootstrap for first-run Owner.
    // An out-of-webroot file proof authorizes the browser; no manually typed key.
    // When absent, the Laravel Owner setup remains disabled.
    'setup_token' => env('PAD_SETUP_TOKEN', ''),
    // Whitelisted Ed25519 public keys, added by the site owner.
    // Without trusted keys, no update artifact may be accepted.
    'release_public_keys' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('PAD_RELEASE_PUBLIC_KEYS', '')))
    )),
    // Trusted plugin publisher public keys (base64, comma-separated).
    'plugin_public_keys' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('PAD_PLUGIN_PUBLIC_KEYS', '')))
    )),
];
