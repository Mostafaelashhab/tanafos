<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\MerchantProfile;
use App\Models\Request;
use App\Notifications\NewLead;
use Illuminate\Support\Collection;

class MatchingService
{
    /** Maximum merchants notified per request. */
    public const MAX_LEADS = 20;

    /** Soft radius (km) used for distance scoring when both sides have geo. */
    public const MAX_DISTANCE_KM = 150;

    /** Score weights — must sum to 1.0. */
    private const WEIGHTS = [
        'category' => 0.35,
        'reputation' => 0.25,
        'distance' => 0.20,
        'win_rate' => 0.10,
        'response' => 0.10,
    ];

    /**
     * Find, score, and persist leads for a published request, notifying each merchant.
     *
     * @return Collection<int, Lead>
     */
    public function match(Request $request): Collection
    {
        if (! $request->isOpen()) {
            return collect();
        }

        $scored = $this->scoredCandidates($request)->take(self::MAX_LEADS);

        return $scored->map(function (array $row) use ($request) {
            /** @var MerchantProfile $profile */
            $profile = $row['profile'];

            $lead = Lead::firstOrNew([
                'request_id' => $request->id,
                'merchant_profile_id' => $profile->id,
            ]);

            // Only (re)score leads the merchant hasn't acted on yet.
            if (! $lead->exists || $lead->status === 'notified') {
                $lead->fill([
                    'quality_score' => $row['score'],
                    'distance_km' => $row['distance'],
                    'status' => 'notified',
                ])->save();

                $profile->user->notify(new NewLead($lead));
            }

            return $lead;
        });
    }

    /**
     * Eligible merchants with their computed score, sorted best-first.
     *
     * @return Collection<int, array{profile: MerchantProfile, score: int, distance: float|null}>
     */
    public function scoredCandidates(Request $request): Collection
    {
        return $this->candidates($request)
            ->map(fn (MerchantProfile $profile) => [
                'profile' => $profile,
                'score' => $this->score($request, $profile),
                'distance' => $this->distanceKm($request, $profile),
            ])
            ->sortByDesc('score')
            ->values();
    }

    /** Hard eligibility filters: verified, can pay (credits or subscription), serves the category. */
    public function candidates(Request $request): Collection
    {
        $categoryIds = $this->acceptableCategoryIds($request);

        return MerchantProfile::query()
            ->with('user')
            ->whereNotNull('verified_at')
            ->where(fn ($q) => $q->where('credits_balance', '>', 0)
                ->orWhere('subscription_tier', '!=', 'none'))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->get();
    }

    /** Request category plus its parent and children — so a parent request reaches child specialists and vice-versa. */
    public function acceptableCategoryIds(Request $request): array
    {
        $category = $request->category;

        $ids = [$category->id];

        if ($category->parent_id) {
            $ids[] = $category->parent_id;
        }

        return array_values(array_unique([
            ...$ids,
            ...$category->children()->pluck('id')->all(),
        ]));
    }

    /** Weighted 0–100 match score. */
    public function score(Request $request, MerchantProfile $profile): int
    {
        $servedIds = $profile->categories->pluck('id')->all();

        // Category: exact request category match beats a parent/child match.
        $category = in_array($request->category_id, $servedIds, true)
            ? 1.0
            : 0.6;

        $reputation = min((float) $profile->rating_avg / 5, 1.0);
        $winRate = min((float) $profile->win_rate / 100, 1.0);

        // Responsiveness: faster is better, neutral when unknown, floored within ~a day.
        $response = $profile->response_minutes_avg === null
            ? 0.5
            : max(0.0, 1 - min($profile->response_minutes_avg / 1440, 1.0));

        // Distance: neutral when either side lacks geo; decays to 0 at MAX_DISTANCE_KM.
        $distance = $this->distanceKm($request, $profile);
        $distanceScore = $distance === null
            ? 0.5
            : max(0.0, 1 - min($distance / self::MAX_DISTANCE_KM, 1.0));

        $total = self::WEIGHTS['category'] * $category
            + self::WEIGHTS['reputation'] * $reputation
            + self::WEIGHTS['distance'] * $distanceScore
            + self::WEIGHTS['win_rate'] * $winRate
            + self::WEIGHTS['response'] * $response;

        return (int) round($total * 100);
    }

    /** Great-circle distance in km, or null if either side lacks coordinates. */
    public function distanceKm(Request $request, MerchantProfile $profile): ?float
    {
        if ($request->lat === null || $request->lng === null
            || $profile->lat === null || $profile->lng === null) {
            return null;
        }

        $earthKm = 6371;
        $lat1 = deg2rad((float) $request->lat);
        $lat2 = deg2rad((float) $profile->lat);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad((float) $profile->lng - (float) $request->lng);

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return round($earthKm * 2 * asin(min(1.0, sqrt($a))), 2);
    }
}
