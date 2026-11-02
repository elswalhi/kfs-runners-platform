<?php

namespace App\Services;

use App\Models\Race;
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
}
