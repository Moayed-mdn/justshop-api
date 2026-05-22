<?php

declare(strict_types=1);

namespace App\Events\Lead;

use App\Models\Lead;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $leadId,
    ) {}
}
