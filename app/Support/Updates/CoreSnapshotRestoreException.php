<?php

namespace App\Support\Updates;

use RuntimeException;

final class CoreSnapshotRestoreException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $automaticRecoveryCompleted)
    {
        parent::__construct($message);
    }
}

