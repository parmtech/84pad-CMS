<?php

namespace Tests\Feature;

use App\Support\Plugins\PluginCatalog;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class PluginCompatibilityTest extends TestCase
{
    public function test_plugin_compatibility_uses_current_configured_core_version(): void
    {
        $catalog = app(PluginCatalog::class);
        Config::set('84pad.core_version', '0.1.0');

        $this->assertFalse($catalog->compatible(['requires_core' => '0.2.0']));
        $this->assertTrue($catalog->compatible(['requires_core' => '0.1.0']));

        Config::set('84pad.core_version', '0.2.0');

        $this->assertTrue($catalog->compatible(['requires_core' => '0.2.0']));
        $this->assertFalse($catalog->compatible(['requires_core' => '0.3.0']));
        $this->assertFalse($catalog->compatible([]));
    }
}
