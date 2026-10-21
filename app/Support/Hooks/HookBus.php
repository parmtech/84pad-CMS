<?php
namespace App\Support\Hooks;

final class HookBus
{
    private array $callbacks = [];
    private int $sequence = 0;

    public function listen(string $name, callable $callback, int $priority = 10): void
    {
        if (! preg_match('/^[a-z][a-z0-9_.:-]{1,80}$/D', $name)) {
            throw new \InvalidArgumentException('Invalid hook name');
        }
        $this->callbacks[$name][] = ['priority'=>$priority,'order'=>$this->sequence++,'callback'=>$callback];
    }

    public function filter(string $name, mixed $value): mixed
    {
        $list = $this->callbacks[$name] ?? [];
        usort($list, static fn(array $a,array $b):int=>[$a['priority'],$a['order']]<=>[$b['priority'],$b['order']]);
        foreach ($list as $entry) {$value = ($entry['callback'])($value);}
        return $value;
    }

    public function dispatch(string $name, mixed $payload = null): void
    {
        $this->filter($name,$payload);
    }
}
