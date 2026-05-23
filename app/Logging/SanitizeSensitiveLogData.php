<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\Observability\MetadataSanitizer;
use Monolog\LogRecord;
use Monolog\Logger;

class SanitizeSensitiveLogData
{
    public function __construct(
        private readonly MetadataSanitizer $metadataSanitizer,
    ) {}

    public function __invoke(\Illuminate\Log\Logger $logger): void
    {
        if (!config('observability.log_redaction.enabled', true)) {
            return;
        }

        /** @var Logger $monolog */
        $monolog = $logger->getLogger();

        $monolog->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(
                message: $this->metadataSanitizer->sanitizeMessage($record->message),
                context: $this->metadataSanitizer->sanitize($record->context),
                extra: $this->metadataSanitizer->sanitize($record->extra),
            );
        });
    }
}
