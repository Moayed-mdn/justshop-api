<?php

declare(strict_types=1);

namespace App\Services\Platform;

class TrendCalculatorService
{
    /**
     * Calculate trend percentage and direction for count metrics
     */
    public function calculateTrend(int $current, int $previous): array
    {
        if ($previous === 0) {
            return [
                'change' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'change' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Calculate trend percentage and direction for revenue metrics
     */
    public function calculateRevenueTrend(float $current, float $previous): array
    {
        // Handle case when both are zero
        if ($previous == 0 && $current == 0) {
            return [
                'change' => 0,
                'direction' => 'neutral',
            ];
        }

        if ($previous == 0) {
            return [
                'change' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'change' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }
}
