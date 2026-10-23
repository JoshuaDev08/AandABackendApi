<?php

namespace App\Services;

use App\Models\RoomRate;
use App\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function calculate(
        RoomType $roomType,
        string $checkIn,
        string $checkOut
    ): array {
        $start = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $checkIn
        );

        $end = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $checkOut
        );

        if (!$start || !$end || $end->lte($start)) {
            throw ValidationException::withMessages([
                'check_out' => [
                    'Check-out must be after check-in.',
                ],
            ]);
        }

        $nights = (int) $start->diffInDays($end);

        if ($nights > 30) {
            throw ValidationException::withMessages([
                'check_out' => [
                    'A stay cannot exceed 30 nights.',
                ],
            ]);
        }

        $plans = $roomType->ratePlans()
            ->where('is_active', true)
            ->with([
                'rates' => fn($query) =>
                $query->where('is_active', true),
            ])
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $breakdown = [];
        $totalCentavos = 0;

        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $selectedPlan = null;
            $selectedRate = null;

            foreach ($plans as $plan) {
                if ($nights < $plan->min_nights) {
                    continue;
                }

                $matchingRates = $plan->rates
                    ->filter(
                        fn(RoomRate $rate) =>
                        $this->matches($rate, $date)
                    )
                    ->sort(function (RoomRate $a, RoomRate $b) {
                        // Within the same plan, dated overrides
                        // take precedence over undated rules.
                        $aSpecificity =
                            ($a->start_date !== null ? 1 : 0)
                            + ($a->end_date !== null ? 1 : 0);

                        $bSpecificity =
                            ($b->start_date !== null ? 1 : 0)
                            + ($b->end_date !== null ? 1 : 0);

                        return ($bSpecificity <=> $aSpecificity)
                            ?: ($a->id <=> $b->id);
                    });

                $rate = $matchingRates->first();

                if ($rate) {
                    $selectedPlan = $plan;
                    $selectedRate = $rate;
                    break;
                }
            }

            if (!$selectedRate) {
                throw ValidationException::withMessages([
                    'rates' => [
                        "No rate configured for {$date->toDateString()}.",
                    ],
                ]);
            }

            // Decimal monetary values are converted to centavos,
            // avoiding floating-point rounding errors.
            [$pesos, $centavos] = explode(
                '.',
                (string) $selectedRate->amount
            );

            $amountCentavos = ((int) $pesos * 100)
                + (int) $centavos;

            $totalCentavos += $amountCentavos;

            $breakdown[] = [
                'date' => $date->toDateString(),
                'day' => $date->format('l'),
                'rate_plan' => $selectedPlan->name,
                'rate_code' => $selectedPlan->code,
                'amount' => $this->money($amountCentavos),
            ];
        }

        return [
            'room_type_id' => $roomType->id,
            'currency' => 'PHP',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights,
            'breakdown' => $breakdown,
            'subtotal' => $this->money($totalCentavos),
            'total' => $this->money($totalCentavos),
        ];
    }

    private function matches(
        RoomRate $rate,
        CarbonImmutable $date
    ): bool {
        $day = $date->toDateString();

        if (
            $rate->start_date
            && $day < $rate->start_date->format('Y-m-d')
        ) {
            return false;
        }

        if (
            $rate->end_date
            && $day > $rate->end_date->format('Y-m-d')
        ) {
            return false;
        }

        $allowedDays = $rate->days_of_week;

        if (
            $allowedDays !== null
            && !in_array($date->dayOfWeekIso, $allowedDays)
        ) {
            return false;
        }

        return true;
    }

    private function money(int $centavos): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($centavos, 100),
            $centavos % 100
        );
    }
}
