<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\Lead\LeadTypeEnum;
use App\Http\Requests\Admin\Lead\ListLeadsRequest;

class ListLeadsDTO
{
    public function __construct(
        public readonly ?LeadTypeEnum $type,
        public readonly LeadStatusEnum|string|null $status,
        public readonly ?string $email,
        public readonly ?string $createdFrom,
        public readonly ?string $createdTo,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListLeadsRequest $request): self
    {
        $type = $request->string('type')->toString();
        $status = $request->string('status')->toString();

        return new self(
            type: $type !== '' ? LeadTypeEnum::from($type) : null,
            status: $status === '' ? null : ($status === 'all' ? 'all' : LeadStatusEnum::from($status)),
            email: $request->filled('email') ? $request->string('email')->toString() : null,
            createdFrom: $request->input('created_from'),
            createdTo: $request->input('created_to'),
            perPage: $request->integer('per_page', 15),
        );
    }
}
