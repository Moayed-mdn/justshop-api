<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

use App\Enums\Lead\LeadTypeEnum;
use App\Http\Requests\Lead\LeadSubmissionRequest;

class CreateLeadDTO
{
    public function __construct(
        public readonly LeadTypeEnum $type,
        public readonly ?string $sourcePage,
        public readonly string $locale,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $company,
        public readonly ?string $phone,
        public readonly string $message,
        public readonly array $metadata,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?string $website,
    ) {}

    public static function fromRequest(
        LeadSubmissionRequest $request,
        LeadTypeEnum $type,
    ): self {
        return new self(
            type: $type,
            sourcePage: $request->input('source_page'),
            locale: (string) ($request->input('locale') ?: app()->getLocale()),
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            company: $request->filled('company') ? $request->string('company')->toString() : null,
            phone: $request->filled('phone') ? $request->string('phone')->toString() : null,
            message: $request->string('message')->toString(),
            metadata: $request->array('metadata'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            website: $request->filled('website') ? $request->string('website')->toString() : null,
        );
    }
}
