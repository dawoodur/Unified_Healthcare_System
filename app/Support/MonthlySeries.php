<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a ['2026-05' => 1200, '2026-07' => 800] map (whatever months a
 * query actually returned rows for) into a full run of N consecutive
 * months ending this month, filling in 0 for any month with no rows —
 * otherwise a chart would silently skip a quiet month instead of showing
 * it as genuinely zero. Shared by AdminAnalyticsController and
 * DoctorAnalyticsController (both build "last 6 months" bar charts).
 */
class MonthlySeries
{
    /**
     * @param Collection $valuesByYearMonth keyed by 'Y-m', from ->pluck('value', 'ym')
     * @param callable $formatter turns a raw value into the display string shown next to the bar
     * @return Collection of ['label' => 'M Y', 'value' => float, 'formatted' => string]
     */
    public static function fill(Collection $valuesByYearMonth, callable $formatter, int $months = 6): Collection
    {
        // startOfMonth() first, THEN subtract — subtracting months from
        // today's raw day-of-month can overflow into the wrong month (e.g.
        // on July 30th, ->subMonths(5) means "Feb 30th", which doesn't
        // exist, so Carbon rolls it over to March 2nd instead). Anchoring
        // to the 1st avoids that entirely, since every month has a day 1.
        return collect(range($months - 1, 0))
            ->map(fn ($monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo)->format('Y-m'))
            ->map(function ($ym) use ($valuesByYearMonth, $formatter) {
                $value = $valuesByYearMonth[$ym] ?? 0;

                // createFromFormat('Y-m', ...) fills in the unspecified day
                // using TODAY's day-of-month — on the 30th/31st that can
                // overflow a shorter month (Feb 30th isn't real, so Carbon
                // rolls it into March) and silently mislabel the bar. Give
                // it an explicit day-1 so there's nothing left to default.
                return [
                    'label' => Carbon::createFromFormat('Y-m-d', $ym . '-01')->format('M Y'),
                    'value' => (float) $value,
                    'formatted' => $formatter($value),
                ];
            });
    }
}
