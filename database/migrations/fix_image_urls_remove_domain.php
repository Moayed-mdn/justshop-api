<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix image_url column to store relative paths instead of absolute URLs.
     * Strips domain and /storage/ prefix from existing URLs.
     */
    public function up(): void
    {
        $appUrl = config('app.url');
        
        DB::table('images')
            ->where('image_url', 'like', $appUrl . '%')
            ->orWhere('image_url', 'like', '/storage/%')
            ->chunkById(100, function ($images) use ($appUrl) {
                foreach ($images as $image) {
                    $normalized = $this->normalizeImagePath($image->image_url, $appUrl);
                    
                    if ($normalized !== $image->image_url) {
                        DB::table('images')
                            ->where('id', $image->id)
                            ->update(['image_url' => $normalized]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse - data loss would occur
    }

    /**
     * Normalize image path by stripping domain and /storage/ prefix.
     */
    private function normalizeImagePath(string $url, string $appUrl): string
    {
        // If it's an external URL (not from our storage), keep as-is
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // Only normalize if it's from our own domain
            if (!str_starts_with($url, $appUrl)) {
                return $url;
            }
            // Strip our domain
            $url = str_replace($appUrl, '', $url);
        }

        // Strip leading /storage/ prefix
        $url = preg_replace('#^/?storage/#', '', $url);

        return $url;
    }
};
