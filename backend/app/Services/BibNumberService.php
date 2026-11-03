<?php

namespace App\Services;

use App\Models\Race;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Collection;

class BibNumberService
{
    /**
     * Lock race rows in a stable order before allocating several numbers in one
     * transaction. This avoids deadlocks for team registrations spanning races.
     */
    public function lockRaces(iterable $raceIds): Collection
    {
        $ids = collect($raceIds)->map(fn ($id) => (int) $id)->unique()->sort()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Race::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function next(int $raceId): int
    {
        $race = Race::query()->whereKey($raceId)->lockForUpdate()->firstOrFail();
        $number = max((int) $race->bib_number_start, (int) $race->next_bib_number);

        $race->update(['next_bib_number' => $number + 1]);

        return $number;
    }

    /** Assign bibs only after the linked payment is confirmed. */
    public function assignForPaidPayment(Payment $payment): void
    {
        if ($payment->status !== 'paid') {
            return;
        }

        $registrationsQuery = Registration::query()
            ->where('payment_status', 'paid')
            ->whereNull('bib_number');

        if ($payment->registration_id) {
            $registrationsQuery->whereKey($payment->registration_id);
        } elseif ($payment->team_id) {
            $registrationsQuery->where('team_id', $payment->team_id);
        } else {
            return;
        }

        $registrations = $registrationsQuery
            ->orderBy('race_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($registrations->isEmpty()) {
            return;
        }

        $races = $this->lockRaces($registrations->pluck('race_id'))->keyBy('id');
        foreach ($registrations as $registration) {
            $race = $races->get($registration->race_id);
            if (! $race) {
                continue;
            }

            $number = max((int) $race->bib_number_start, (int) $race->next_bib_number);
            $race->update(['next_bib_number' => $number + 1]);
            $registration->update(['bib_number' => $number]);
        }
    }
}
