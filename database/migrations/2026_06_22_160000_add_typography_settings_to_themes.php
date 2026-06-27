<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Theme\Theme;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add/update typography configuration to existing themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            
            // Add comprehensive typography configuration if not already present
            if (!isset($settings['typography']) || count($settings['typography']) < 7) {
                $settings['typography'] = $this->getDefaultTypographySettings();
                $theme->settings = $settings;
                $theme->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove typography configuration from themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            unset($settings['typography']);
            $theme->settings = $settings;
            $theme->save();
        });
    }

    /**
     * Get default typography settings
     */
    private function getDefaultTypographySettings(): array
    {
        return [
            'headingFont' => 'Inter',
            'bodyFont' => 'Inter',
            'headingWeight' => 'semibold',     // normal, medium, semibold, bold
            'bodyWeight' => 'normal',          // normal, medium, semibold, bold
            'baseFontSize' => 'base',          // sm (14px), base (16px), lg (18px)
            'lineHeight' => 'normal',          // tight, normal, relaxed
            'letterSpacing' => 'normal',       // tight, normal, wide
        ];
    }
};
