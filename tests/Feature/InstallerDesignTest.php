<?php

namespace Tests\Feature;

use Tests\TestCase;

final class InstallerDesignTest extends TestCase
{
    public function test_host_and_owner_wizards_share_local_english_only_visual_system(): void
    {
        $host = file_get_contents(base_path('public/84pad-bootstrap.php'));
        $owner = file_get_contents(base_path('resources/views/auth/first-run.blade.php'));
        $css = file_get_contents(base_path('public/css/84pad-installer.css'));

        $this->assertIsString($host);
        $this->assertIsString($owner);
        $this->assertIsString($css);

        foreach ([$host, $owner] as $page) {
            $this->assertStringContainsString('<html lang="en">', $page);
            $this->assertStringContainsString('/css/84pad-installer.css', $page);
            $this->assertStringNotContainsString('<style>', $page);
            // The public installer uses English; do not introduce Punjabi copy.
            $this->assertDoesNotMatchRegularExpression('/[\x{0A00}-\x{0A7F}]/u', $page);
        }

        // Respect accessibility preferences and keep CSS self-hosted.
        $this->assertStringContainsString('prefers-reduced-motion:reduce', $css);
        $this->assertStringContainsString('@media(max-width:570px)', $css);
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringContainsString("style-src 'self'", $host);
    }
}
