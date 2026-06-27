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
        // Add/update color configuration to existing themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            
            // Add comprehensive color configuration if not already present
            if (!isset($settings['colors']) || count($settings['colors']) < 9) {
                $settings['colors'] = $this->getDefaultColorSettings();
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
        // Remove color configuration from themes' settings
        Theme::all()->each(function ($theme) {
            $settings = $theme->settings ?? [];
            unset($settings['colors']);
            $theme->settings = $settings;
            $theme->save();
        });
    }

    /**
     * Get default color settings matching frontend design system
     */
    private function getDefaultColorSettings(): array
    {
        return [
            'primary' => '#3B82F6',      // Blue-500 - Main brand color
            'secondary' => '#10B981',    // Green-500 - Secondary accent
            'background' => '#FFFFFF',   // White - Main background
            'text' => '#1F2937',         // Gray-800 - Primary text
            'textMuted' => '#6B7280',    // Gray-500 - Secondary/muted text
            'border' => '#E5E7EB',       // Gray-200 - Default borders
            'success' => '#10B981',      // Green-500 - Success states
            'error' => '#EF4444',        // Red-500 - Error states
            'warning' => '#F59E0B',      // Amber-500 - Warning states
        ];
    }
};
