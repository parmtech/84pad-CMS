<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PluginCatalogStructureTest extends TestCase
{
    public function test_demo_manifest_is_valid_json(): void
    {
        $path = dirname(__DIR__,2).'/plugins/demo-widget/plugin.json';
        $manifest = json_decode((string) file_get_contents($path),true,8,JSON_THROW_ON_ERROR);
        $this->assertSame('demo-widget',$manifest['id']);
        $this->assertSame('0.1.0',$manifest['version']);
    }
}
