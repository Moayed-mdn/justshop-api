<?php

declare(strict_types=1);

namespace App\Actions\Lead;

use App\DTOs\Lead\CreateLeadDTO;
use App\Enums\Lead\LeadStatusEnum;
use App\Events\Lead\LeadSubmitted;
use App\Models\Lead;
use App\Repositories\Lead\LeadRepository;
use App\Services\Lead\LeadSanitizerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLeadAction
{
    public function __construct(
        private LeadRepository $repository,
        private LeadSanitizerService $sanitizer,
    ) {}

    public function execute(CreateLeadDTO $dto): Lead
    {
        $payload = $this->buildPayload($dto);

        if ($payload['status'] !== LeadStatusEnum::SPAM->value) {
            $this->guardAgainstDuplicate(
                email: $payload['email'],
                message: $payload['message'],
                ipAddress: $payload['ip_address'],
            );
        }

        $lead = DB::transaction(function () use ($payload): Lead {
            return $this->repository->create($payload);
        });

        if ($lead->status !== LeadStatusEnum::SPAM) {
            LeadSubmitted::dispatch($lead);
        }

        return $lead;
    }

    private function buildPayload(CreateLeadDTO $dto): array
    {
        $name = $this->sanitizer->sanitizeText($dto->name);
        $email = strtolower($this->sanitizer->sanitizeText($dto->email));
        $company = $this->sanitizer->sanitizeNullable($dto->company);
        $phone = $this->sanitizer->sanitizeNullable($dto->phone);
        $message = $this->sanitizer->sanitizeMessage($dto->message);
        $sourcePage = $this->sanitizer->sanitizeNullable($dto->sourcePage);
        $locale = $this->sanitizer->sanitizeText($dto->locale);
        $metadata = $this->sanitizer->sanitizeMetadata($dto->metadata);
        $userAgent = $this->sanitizer->sanitizeNullable($dto->userAgent);
        $website = $this->sanitizer->sanitizeNullable($dto->website);

        return [
            'type' => $dto->type->value,
            'status' => $website === null ? LeadStatusEnum::NEW->value : LeadStatusEnum::SPAM->value,
            'source_page' => $sourcePage,
            'locale' => $locale,
            'name' => $name,
            'email' => $email,
            'company' => $company,
            'phone' => $phone,
            'message' => $message,
            'metadata' => $metadata !== [] ? $metadata : null,
            'ip_address' => $dto->ipAddress,
            'user_agent' => $userAgent,
        ];
    }

    private function guardAgainstDuplicate(
        string $email,
        string $message,
        ?string $ipAddress,
    ): void {
        $duplicate = $this->repository->findRecentDuplicate(
            email: $email,
            message: $message,
            ipAddress: $ipAddress,
            since: now()->subMinutes(
                (int) config('lead.spam.duplicate_window_minutes', 10)
            ),
        );

        if ($duplicate !== null) {
            throw ValidationException::withMessages([
                'message' => __('lead.duplicate_submission'),
            ]);
        }
    }
}
