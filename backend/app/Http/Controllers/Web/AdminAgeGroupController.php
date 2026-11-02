<?php

namespace App\Http\Controllers\Web;

use App\Models\AgeGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminAgeGroupController
{
    public function index()
    {
        $ageGroups = AgeGroup::query()->orderBy('sort_order')->orderBy('min_age')->get();

        return view('admin.age-groups', compact('ageGroups'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $this->ensureRangeDoesNotOverlap($data);

        AgeGroup::query()->create($data);

        return back()->with('success', __('Age group created.'));
    }

    public function update(Request $request, AgeGroup $ageGroup)
    {
        $data = $this->validatedData($request, $ageGroup);
        $this->ensureRangeDoesNotOverlap($data, $ageGroup);

        $ageGroup->update($data);

        return back()->with('success', __('Age group updated.'));
    }

    public function destroy(AgeGroup $ageGroup)
    {
        $ageGroup->delete();

        return back()->with('success', __('Age group deleted.'));
    }

    private function validatedData(Request $request, ?AgeGroup $ageGroup = null): array
    {
        $uniqueName = Rule::unique('age_groups', 'name');
        if ($ageGroup) {
            $uniqueName->ignore($ageGroup->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'min_age' => ['required', 'integer', 'min:0', 'max:120'],
            'max_age' => ['required', 'integer', 'gte:min_age', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
    }

    private function ensureRangeDoesNotOverlap(array $data, ?AgeGroup $ageGroup = null): void
    {
        $query = AgeGroup::query()
            ->where('min_age', '<=', $data['max_age'])
            ->where('max_age', '>=', $data['min_age']);

        if ($ageGroup) {
            $query->where('id', '!=', $ageGroup->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'age_range' => __('This age range overlaps another age group. Use non-overlapping ranges.'),
            ]);
        }
    }
}
