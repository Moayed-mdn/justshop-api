<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
