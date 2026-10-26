<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\RaceKitPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicPromoCodeController extends Controller
{
    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'race' => ['nullable', 'string', 'exists:races,slug'],
            'kit' => ['nullable', Rule::in(['full', 'basic'])],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $currency = 'EGP';
        $originalAmount = 0.00;

        if ($request->filled('total') && (float) $request->input('total') > 0) {
            $originalAmount = (float) $request->input('total');
        } elseif ($request->filled('race') && $request->filled('kit')) {
            $race = Race::query()->where('slug', $validated['race'])->where('is_active', true)->first();
            if (! $race) {
                return response()->json(['valid' => false, 'message' => 'Selected race is not active.'], 422);
            }

            $price = RaceKitPrice::query()->where('race_id', $race->id)->where('kit_type', $validated['kit'])->where('is_active', true)->first();
            if (! $price) {
                return response()->json(['valid' => false, 'message' => 'Price for selected kit is not available.'], 422);
            }
            $originalAmount = (float) $price->price;
            $currency = $price->currency;
        }

        $cleanCode = strtoupper(trim($validated['code']));
        $promo = PromoCode::query()->where('code', $cleanCode)->first();

        if (! $promo) {
            return response()->json(['valid' => false, 'message' => 'Invalid promo code.'], 422);
        }

        if (! $promo->is_active) {
            return response()->json(['valid' => false, 'message' => 'This promo code is currently inactive.'], 422);
        }

        if (isset($race) && ! $promo->isValidForRace($race->id)) {
            return response()->json(['valid' => false, 'message' => 'This promo code is not valid for ' . $race->name . '.'], 422);
        }

        if ($promo->valid_from && now()->lt($promo->valid_from)) {
            return response()->json(['valid' => false, 'message' => 'This promo code is not yet valid.'], 422);
        }

        if ($promo->valid_until && now()->gt($promo->valid_until)) {
            return response()->json(['valid' => false, 'message' => 'This promo code has expired.'], 422);
        }

        if ($promo->max_uses !== null && $promo->times_used >= $promo->max_uses) {
            return response()->json(['valid' => false, 'message' => 'This promo code has reached its maximum usage limit.'], 422);
        }

        $discountAmount = $promo->calculateDiscount($originalAmount);
        $finalAmount = max(0.00, round($originalAmount - $discountAmount, 2));

        $discountText = $promo->discount_type === 'percentage'
            ? ((float) $promo->discount_value . '% off')
            : (number_format($discountAmount, 2) . ' ' . $currency . ' off');

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'discount_type' => $promo->discount_type,
            'discount_value' => (float) $promo->discount_value,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
            'currency' => $currency,
            'message' => "Promo code applied ({$discountText})!",
        ]);
    }
}
