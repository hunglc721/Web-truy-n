<?php

namespace App\Services;

use App\Data\RecommendationPreference;
use App\Models\RecommendationConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecommendationConversationService
{
    /** User must come from trusted backend authentication, never a client-supplied user ID. */
    public function getOrCreate(?User $user = null, ?string $sessionToken = null): RecommendationConversation
    {
        if ($user !== null) {
            $this->validateMember($user, $sessionToken);

            return RecommendationConversation::firstOrCreate(['user_id' => $user->getKey()], [
                'session_token' => null, 'preferences_json' => RecommendationPreference::fromArray([])->toArray(),
            ]);
        }
        if ($sessionToken !== null) {
            // Unknown tokens cannot create a record with a client-chosen identity.
            return $this->ownedQuery(null, $sessionToken)->firstOrFail();
        }

        return RecommendationConversation::create([
            // Canonical lowercase hex avoids case-insensitive DB collation matching token variants.
            'session_token' => bin2hex(random_bytes(32)),
            'preferences_json' => RecommendationPreference::fromArray([])->toArray(),
        ]);
    }

    public function getPreference(int $conversationId, ?User $user = null, ?string $sessionToken = null): RecommendationPreference
    {
        $conversation = $this->ownedQuery($user, $sessionToken)->findOrFail($conversationId);

        return RecommendationPreference::fromArray($conversation->preferences_json);
    }

    public function updatePreference(int $conversationId, RecommendationPreference $delta, ?User $user = null, ?string $sessionToken = null): RecommendationPreference
    {
        return DB::transaction(function () use ($conversationId, $delta, $user, $sessionToken) {
            // Read fresh state under a row lock, instead of merging an earlier model snapshot.
            $conversation = $this->ownedQuery($user, $sessionToken)->lockForUpdate()->findOrFail($conversationId);
            $merged = RecommendationPreference::fromArray($conversation->preferences_json)->merge($delta);
            $conversation->update(['preferences_json' => $merged->toArray()]);

            return $merged;
        }, 3);
    }

    private function ownedQuery(?User $user, ?string $sessionToken): Builder
    {
        $query = RecommendationConversation::query();
        if ($user !== null) {
            $this->validateMember($user, $sessionToken);

            return $query->where('user_id', $user->getKey())->whereNull('session_token');
        }
        if ($sessionToken === null || ! preg_match('/\A[a-f0-9]{64}\z/', $sessionToken)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNull('user_id')->where('session_token', $sessionToken);
    }

    private function validateMember(User $user, ?string $sessionToken): void
    {
        if (! $user->exists || $user->getKey() === null || $sessionToken !== null) {
            throw new InvalidArgumentException('Use a persisted authenticated user without a guest token.');
        }
    }
}
