<?php

namespace App\Enums\Theme;

enum AssetTypeEnum: string
{
    case LOGO = 'logo';
    case FAVICON = 'favicon';
    case BANNER = 'banner';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case OTHER = 'other';

    /**
     * Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::LOGO => 'Logo',
            self::FAVICON => 'Favicon',
            self::BANNER => 'Banner Image',
            self::IMAGE => 'Image',
            self::VIDEO => 'Video',
            self::DOCUMENT => 'Document',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get options for API responses
     */
    public static function options(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }

    /**
     * Get allowed MIME types for this asset type
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::LOGO, self::FAVICON, self::BANNER, self::IMAGE => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/svg+xml',
                'image/webp',
            ],
            self::VIDEO => [
                'video/mp4',
                'video/webm',
                'video/ogg',
            ],
            self::DOCUMENT => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            self::OTHER => [],
        };
    }
}
