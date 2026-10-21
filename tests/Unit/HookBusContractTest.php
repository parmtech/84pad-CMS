<?php

namespace Tests\Unit;

use App\Support\Hooks\HookBus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HookBusContractTest extends TestCase
{
    public function test_filters_sort_by_priority_and_preserve_equal_priority_registration_order(): void
    {
        $bus = new HookBus();
        $bus->listen('core.page_title', fn (string $v): string => $v.'-last', 20);
        $bus->listen('core.page_title', fn (string $v): string => $v.'-first', 5);
        $bus->listen('core.page_title', fn (string $v): string => $v.'-mid-a', 10);
        $bus->listen('core.page_title', fn (string $v): string => $v.'-mid-b', 10);

        $this->assertSame(
            'title-first-mid-a-mid-b-last',
            $bus->filter('core.page_title', 'title')
        );
    }

    public function test_dispatch_invokes_action_listener(): void
    {
        $bus = new HookBus();
        $received = [];
        $bus->listen('core.ready', static function ($data) use (&$received): void {
            $received[] = $data;
        });
        $bus->dispatch('core.ready', ['ready' => true]);

        $this->assertSame([['ready' => true]], $received);
    }

    public function test_unknown_hook_returns_original_value(): void
    {
        $this->assertSame('value', (new HookBus())->filter('core.unknown', 'value'));
    }

    public function test_invalid_hook_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new HookBus())->listen('../bad-hook', static fn ($v) => $v);
    }
}
