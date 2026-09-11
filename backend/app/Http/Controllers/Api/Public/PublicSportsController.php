<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SportsCampResource;
use App\Http\Resources\SportsMatchResource;
use App\Http\Resources\SportsPlayerResource;
use App\Http\Resources\SportsStaffResource;
use App\Http\Resources\SportsTeamResource;
use App\Http\Resources\SportsTournamentResource;
use App\Models\SportsCamp;
use App\Models\SportsJoinRequest;
use App\Models\SportsMatch;
use App\Models\SportsPlayer;
use App\Models\SportsStaff;
use App\Models\SportsTeam;
use App\Models\SportsTournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicSportsController extends Controller
{
    public function overview(): JsonResponse
    {
        $teams = SportsTeam::query()
            ->publicVisible()
            ->withCount(['players as players_count' => fn ($q) => $q->where('is_public', true)])
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        $nationalPlayers = SportsPlayer::query()->publicVisible()->national()->orderByDesc('goals')->orderBy('name_ar')->get();

        return response()->json([
            'teams' => SportsTeamResource::collection($teams)->resolve(),
            'national_players_count' => $nationalPlayers->count(),
            'join_open' => true,
        ]);
    }

    public function team(SportsTeam $team): SportsTeamResource
    {
        abort_unless($team->is_public, 404);

        $team->loadCount(['players as players_count' => fn ($q) => $q->where('is_public', true)]);
        $team->setRelation('players', $team->players()->publicVisible()->orderBy('sort_order')->orderBy('name_ar')->get());
        $team->setRelation('staff', $team->staff()->publicVisible()->orderBy('sort_order')->get());
        $team->setRelation('matches', $team->matches()->publicVisible()->latest('played_on')->latest('id')->get());
        $team->setRelation('trainings', $team->trainings()->where('is_public', true)->orderBy('weekday')->orderBy('starts_at')->get());
        $team->setRelation('tournaments', $team->tournaments()->publicVisible()->latest('id')->get());

        return new SportsTeamResource($team);
    }

    public function national(): JsonResponse
    {
        $players = SportsPlayer::query()->publicVisible()->national()->orderByDesc('goals')->orderBy('name_ar')->get();
        $staff = SportsStaff::query()->publicVisible()->where('is_national', true)->orderBy('kind')->orderBy('sort_order')->get();
        $matches = SportsMatch::query()->publicVisible()->where('is_national', true)->latest('played_on')->latest('id')->get();
        $tournaments = SportsTournament::query()->publicVisible()->where('is_national', true)->latest('id')->get();
        $camps = SportsCamp::query()->publicVisible()->latest('starts_on')->latest('id')->get();
        $scorers = $players->sortByDesc('goals')->values()->take(12);

        return response()->json([
            'players' => SportsPlayerResource::collection($players)->resolve(),
            'staff' => SportsStaffResource::collection($staff)->resolve(),
            'matches' => SportsMatchResource::collection($matches)->resolve(),
            'tournaments' => SportsTournamentResource::collection($tournaments)->resolve(),
            'camps' => SportsCampResource::collection($camps)->resolve(),
            'scorers' => SportsPlayerResource::collection($scorers)->resolve(),
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $key = 'sports-join:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json(['message' => 'Too many requests. Please try again later.'], 429);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'age_category' => ['nullable', Rule::in(SportsTeam::AGE_CATEGORIES)],
            'position' => ['nullable', 'string', 'max:80'],
            'sports_team_id' => ['nullable', 'integer', 'exists:sports_teams,id'],
            'for_national' => ['sometimes', 'boolean'],
            'message' => ['nullable', 'string', 'max:4000'],
        ]);

        RateLimiter::hit($key, 3600);

        $item = SportsJoinRequest::query()->create([
            ...$data,
            'for_national' => (bool) ($data['for_national'] ?? false),
            'status' => 'new',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Join request received.',
            'reference' => $item->reference,
        ], 201);
    }
}
