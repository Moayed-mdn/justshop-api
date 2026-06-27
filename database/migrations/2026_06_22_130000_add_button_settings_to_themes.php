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
        // Add button configuration to existing themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            
            // Add button configuration if not already present
            if (!isset($settings['buttons'])) {
                $settings['buttons'] = $this->getDefaultButtonSettings();
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
        // Remove button configuration from themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            unset($settings['buttons']);
            $theme->settings = $settings;
            $theme->save();
        });
    }

    /**
     * Get default button settings
     */
    private function getDefaultButtonSettings(): array
    {
        return [
            'primary' => [
                'backgroundColor' => '#3B82F6',
                'textColor' => '#FFFFFF',
                'borderColor' => '#3B82F6',
                'borderWidth' => 0,
                'borderRadius' => 'full', // none, sm, md, lg, full
                'paddingX' => 'lg', // sm, md, lg, xl
                'paddingY' => 'md', // sm, md, lg
                'fontSize' => 'base', // sm, base, lg
                'fontWeight' => 'semibold', // normal, medium, semibold, bold
                'hoverEffect' => 'opacity', // opacity, darken, lift, scale
            ],
            'secondary' => [
                'backgroundColor' => 'rgba(255, 255, 255, 0.15)',
                'textColor' => '#FFFFFF',
                'borderColor' => 'rgba(255, 255, 255, 0.4)',
                'borderWidth' => 1,
                'borderRadius' => 'full',
                'paddingX' => 'lg',
                'paddingY' => 'md',
                'fontSize' => 'base',
                'fontWeight' => 'semibold',
                'hoverEffect' => 'opacity',
            ],
            'outline' => [
                'backgroundColor' => 'transparent',
                'textColor' => '#FFFFFF',
                'borderColor' => 'rgba(255, 255, 255, 0.6)',
                'borderWidth' => 2,
                'borderRadius' => 'full',
                'paddingX' => 'lg',
                'paddingY' => 'md',
                'fontSize' => 'base',
                'fontWeight' => 'semibold',
                'hoverEffect' => 'opacity',
            ],
        ];
    }
};
