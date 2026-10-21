<?php
namespace Tests\Unit;

use App\Support\Hooks\HookBus;
use PHPUnit\Framework\TestCase;

class HookBusTest extends TestCase
{
    public function test_filters_execute_in_priority_order(): void
    {
        $hooks = new HookBus;
        $hooks->listen('demo.title',fn(string $v):string=>$v.'B',20);
        $hooks->listen('demo.title',fn(string $v):string=>$v.'A',10);
        $this->assertSame('startAB',$hooks->filter('demo.title','start'));
    }
}
