<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SportsCampResource;
use App\Http\Resources\SportsJoinRequestResource;
use App\Http\Resources\SportsMatchResource;
use App\Http\Resources\SportsPlayerResource;
use App\Http\Resources\SportsStaffResource;
use App\Http\Resources\SportsTeamResource;
use App\Http\Resources\SportsTournamentResource;
use App\Http\Resources\SportsTrainingResource;
use App\Models\SportsCamp;
use App\Models\SportsJoinRequest;
use App\Models\SportsMatch;
use App\Models\SportsPlayer;
use App\Models\SportsStaff;
use App\Models\SportsTeam;
use App\Models\SportsTournament;
use App\Models\SportsTraining;
use App\Support\AssertsSportsSecretariat;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminSportsController extends Controller
{
    use AssertsSportsSecretariat;

    public function teamsIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsTeamResource::collection(
            SportsTeam::query()
                ->withCount('players')
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.trim((string) $request->input('search')).'%';
                    $q->where(fn ($inner) => $inner->where('name_ar', 'like', $term)->orWhere('name_fr', 'like', $term));
                })
                ->orderBy('sort_order')
                ->orderBy('name_ar')
                ->paginate($request->integer('per_page', 40))
        );
    }

    public function teamsStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsTeam::query()->create($this->teamData($request));
        $this->storePhoto($request, $item, 'photo_path', 'sports_teams');

        return (new SportsTeamResource($item->fresh()->loadCount('players')))->response()->setStatusCode(201);
    }

    public function teamsShow(Request $request, string $code, SportsTeam $team): SportsTeamResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return new SportsTeamResource(
            $team->loadCount('players')->load([
                'players' => fn ($q) => $q->orderBy('sort_order')->orderBy('name_ar'),
                'staff' => fn ($q) => $q->orderBy('sort_order'),
                'matches' => fn ($q) => $q->latest('played_on')->latest('id'),
                'trainings' => fn ($q) => $q->orderBy('weekday')->orderBy('starts_at'),
                'tournaments' => fn ($q) => $q->latest('id'),
            ])
        );
    }

    public function teamsUpdate(Request $request, string $code, SportsTeam $team): SportsTeamResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $team->update($this->teamData($request, $team));
        $this->storePhoto($request, $team, 'photo_path', 'sports_teams');

        return new SportsTeamResource($team->fresh()->loadCount('players'));
    }

    public function teamsDestroy(Request $request, string $code, SportsTeam $team): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        StoredFileStore::forget($team->photo_path);
        $team->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function playersIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsPlayerResource::collection(
            SportsPlayer::query()
                ->with('team')
                ->when($request->filled('team_id'), fn ($q) => $q->where('sports_team_id', $request->integer('team_id')))
                ->when($request->boolean('national'), fn ($q) => $q->where('is_national', true))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.trim((string) $request->input('search')).'%';
                    $q->where(fn ($inner) => $inner->where('name_ar', 'like', $term)->orWhere('name_fr', 'like', $term));
                })
                ->orderByDesc('goals')
                ->orderBy('name_ar')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function playersStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsPlayer::query()->create($this->playerData($request));
        $this->storePhoto($request, $item, 'photo_path', 'sports_players');

        return (new SportsPlayerResource($item->fresh()->load('team')))->response()->setStatusCode(201);
    }

    public function playersUpdate(Request $request, string $code, SportsPlayer $player): SportsPlayerResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $player->update($this->playerData($request, $player));
        $this->storePhoto($request, $player, 'photo_path', 'sports_players');

        return new SportsPlayerResource($player->fresh()->load('team'));
    }

    public function playersDestroy(Request $request, string $code, SportsPlayer $player): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        StoredFileStore::forget($player->photo_path);
        $player->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function staffIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsStaffResource::collection(
            SportsStaff::query()
                ->when($request->filled('team_id'), fn ($q) => $q->where('sports_team_id', $request->integer('team_id')))
                ->when($request->boolean('national'), fn ($q) => $q->where('is_national', true))
                ->orderBy('kind')
                ->orderBy('sort_order')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function staffStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsStaff::query()->create($this->staffData($request));
        $this->storePhoto($request, $item, 'photo_path', 'sports_staff');

        return (new SportsStaffResource($item->fresh()))->response()->setStatusCode(201);
    }

    public function staffUpdate(Request $request, string $code, SportsStaff $staff): SportsStaffResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $staff->update($this->staffData($request, $staff));
        $this->storePhoto($request, $staff, 'photo_path', 'sports_staff');

        return new SportsStaffResource($staff->fresh());
    }

    public function staffDestroy(Request $request, string $code, SportsStaff $staff): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        StoredFileStore::forget($staff->photo_path);
        $staff->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function matchesIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsMatchResource::collection(
            SportsMatch::query()
                ->when($request->filled('team_id'), fn ($q) => $q->where('sports_team_id', $request->integer('team_id')))
                ->when($request->boolean('national'), fn ($q) => $q->where('is_national', true))
                ->latest('played_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 40))
        );
    }

    public function matchesStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsMatch::query()->create($this->matchData($request));

        return (new SportsMatchResource($item))->response()->setStatusCode(201);
    }

    public function matchesUpdate(Request $request, string $code, SportsMatch $match): SportsMatchResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $match->update($this->matchData($request, $match));

        return new SportsMatchResource($match->fresh());
    }

    public function matchesDestroy(Request $request, string $code, SportsMatch $match): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        $match->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function trainingsStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsTraining::query()->create($this->trainingData($request));

        return (new SportsTrainingResource($item))->response()->setStatusCode(201);
    }

    public function trainingsUpdate(Request $request, string $code, SportsTraining $training): SportsTrainingResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $training->update($this->trainingData($request, $training));

        return new SportsTrainingResource($training->fresh());
    }

    public function trainingsDestroy(Request $request, string $code, SportsTraining $training): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        $training->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function tournamentsIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsTournamentResource::collection(
            SportsTournament::query()
                ->when($request->filled('team_id'), fn ($q) => $q->where('sports_team_id', $request->integer('team_id')))
                ->when($request->boolean('national'), fn ($q) => $q->where('is_national', true))
                ->latest('id')
                ->paginate($request->integer('per_page', 40))
        );
    }

    public function tournamentsStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsTournament::query()->create($this->tournamentData($request));

        return (new SportsTournamentResource($item))->response()->setStatusCode(201);
    }

    public function tournamentsUpdate(Request $request, string $code, SportsTournament $tournament): SportsTournamentResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $tournament->update($this->tournamentData($request, $tournament));

        return new SportsTournamentResource($tournament->fresh());
    }

    public function tournamentsDestroy(Request $request, string $code, SportsTournament $tournament): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        $tournament->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function campsIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsCampResource::collection(
            SportsCamp::query()->latest('starts_on')->latest('id')->paginate($request->integer('per_page', 40))
        );
    }

    public function campsStore(Request $request, string $code): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.create');
        $item = SportsCamp::query()->create($this->campData($request));

        return (new SportsCampResource($item))->response()->setStatusCode(201);
    }

    public function campsUpdate(Request $request, string $code, SportsCamp $camp): SportsCampResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $camp->update($this->campData($request, $camp));

        return new SportsCampResource($camp->fresh());
    }

    public function campsDestroy(Request $request, string $code, SportsCamp $camp): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        $camp->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function joinIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.view');

        return SportsJoinRequestResource::collection(
            SportsJoinRequest::query()
                ->with('team')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.trim((string) $request->input('search')).'%';
                    $q->where(fn ($inner) => $inner->where('full_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('reference', 'like', $term));
                })
                ->latest('id')
                ->paginate($request->integer('per_page', 40))
        );
    }

    public function joinUpdate(Request $request, string $code, SportsJoinRequest $joinRequest): SportsJoinRequestResource
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.update');
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(SportsJoinRequest::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $joinRequest->update($data);

        return new SportsJoinRequestResource($joinRequest->fresh()->load('team'));
    }

    public function joinDestroy(Request $request, string $code, SportsJoinRequest $joinRequest): JsonResponse
    {
        $this->assertSports($code);
        $this->authorizeSport($request, 'sport.delete');
        $joinRequest->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function teamData(Request $request, ?SportsTeam $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'name_ar' => [$required, 'string', 'max:190'],
            'name_fr' => [$required, 'string', 'max:190'],
            'age_category' => [$required, Rule::in(SportsTeam::AGE_CATEGORIES)],
            'sport' => ['nullable', 'string', 'max:40'],
            'coach_ar' => ['nullable', 'string', 'max:190'],
            'coach_fr' => ['nullable', 'string', 'max:190'],
            'manager_ar' => ['nullable', 'string', 'max:190'],
            'manager_fr' => ['nullable', 'string', 'max:190'],
            'ranking' => ['nullable', 'integer', 'min:1', 'max:999'],
            'points' => ['nullable', 'integer', 'min:0', 'max:999'],
            'played' => ['nullable', 'integer', 'min:0', 'max:999'],
            'wins' => ['nullable', 'integer', 'min:0', 'max:999'],
            'draws' => ['nullable', 'integer', 'min:0', 'max:999'],
            'losses' => ['nullable', 'integer', 'min:0', 'max:999'],
            'goals_for' => ['nullable', 'integer', 'min:0', 'max:999'],
            'goals_against' => ['nullable', 'integer', 'min:0', 'max:999'],
            'notes_ar' => ['nullable', 'string', 'max:4000'],
            'notes_fr' => ['nullable', 'string', 'max:4000'],
            'is_public' => ['sometimes'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => UploadRules::image(5120),
        ]);
        unset($data['photo']);
        $data['sport'] = $data['sport'] ?? 'football';
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function playerData(Request $request, ?SportsPlayer $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'name_ar' => [$required, 'string', 'max:190'],
            'name_fr' => [$required, 'string', 'max:190'],
            'sports_team_id' => ['nullable'],
            'is_national' => ['sometimes'],
            'position' => ['nullable', 'string', 'max:80'],
            'number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'birth_date' => ['nullable', 'date'],
            'appearances' => ['nullable', 'integer', 'min:0', 'max:999'],
            'goals' => ['nullable', 'integer', 'min:0', 'max:999'],
            'assists' => ['nullable', 'integer', 'min:0', 'max:999'],
            'yellow_cards' => ['nullable', 'integer', 'min:0', 'max:99'],
            'red_cards' => ['nullable', 'integer', 'min:0', 'max:99'],
            'is_public' => ['sometimes'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => UploadRules::image(5120),
        ]);
        unset($data['photo']);
        if (array_key_exists('sports_team_id', $data)) {
            $data['sports_team_id'] = $this->optionalTeamId($data['sports_team_id']);
        }
        if ($request->exists('is_national')) {
            $data['is_national'] = $this->boolish($request, 'is_national');
        }
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function staffData(Request $request, ?SportsStaff $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'name_ar' => [$required, 'string', 'max:190'],
            'name_fr' => [$required, 'string', 'max:190'],
            'role_ar' => ['nullable', 'string', 'max:190'],
            'role_fr' => ['nullable', 'string', 'max:190'],
            'kind' => ['nullable', Rule::in(SportsStaff::KINDS)],
            'sports_team_id' => ['nullable'],
            'is_national' => ['sometimes'],
            'is_public' => ['sometimes'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => UploadRules::image(5120),
        ]);
        unset($data['photo']);
        $data['kind'] = $data['kind'] ?? 'technical';
        if (array_key_exists('sports_team_id', $data)) {
            $data['sports_team_id'] = $this->optionalTeamId($data['sports_team_id']);
        }
        if ($request->exists('is_national')) {
            $data['is_national'] = $this->boolish($request, 'is_national');
        }
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function matchData(Request $request, ?SportsMatch $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'opponent_ar' => [$required, 'string', 'max:190'],
            'opponent_fr' => [$required, 'string', 'max:190'],
            'competition_ar' => ['nullable', 'string', 'max:190'],
            'competition_fr' => ['nullable', 'string', 'max:190'],
            'played_on' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:190'],
            'is_home' => ['sometimes'],
            'goals_for' => ['nullable', 'integer', 'min:0', 'max:99'],
            'goals_against' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['nullable', Rule::in(SportsMatch::STATUSES)],
            'sports_team_id' => ['nullable'],
            'is_national' => ['sometimes'],
            'is_public' => ['sometimes'],
        ]);
        $data['status'] = $data['status'] ?? 'played';
        if (array_key_exists('sports_team_id', $data)) {
            $data['sports_team_id'] = $this->optionalTeamId($data['sports_team_id']);
        }
        foreach (['is_home', 'is_national', 'is_public'] as $flag) {
            if ($request->exists($flag)) {
                $data[$flag] = $this->boolish($request, $flag);
            }
        }

        return $data;
    }

    private function trainingData(Request $request, ?SportsTraining $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'sports_team_id' => [$required],
            'weekday' => [$required, 'integer', 'min:1', 'max:7'],
            'starts_at' => ['nullable', 'string', 'max:8'],
            'ends_at' => ['nullable', 'string', 'max:8'],
            'location' => ['nullable', 'string', 'max:190'],
            'notes_ar' => ['nullable', 'string', 'max:2000'],
            'notes_fr' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes'],
        ]);
        $data['sports_team_id'] = $this->optionalTeamId($data['sports_team_id'] ?? null);
        abort_unless($data['sports_team_id'], 422);
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function tournamentData(Request $request, ?SportsTournament $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'title_ar' => [$required, 'string', 'max:190'],
            'title_fr' => [$required, 'string', 'max:190'],
            'season' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:190'],
            'ranking' => ['nullable', 'string', 'max:80'],
            'notes_ar' => ['nullable', 'string', 'max:4000'],
            'notes_fr' => ['nullable', 'string', 'max:4000'],
            'sports_team_id' => ['nullable'],
            'is_national' => ['sometimes'],
            'is_public' => ['sometimes'],
        ]);
        if (array_key_exists('sports_team_id', $data)) {
            $data['sports_team_id'] = $this->optionalTeamId($data['sports_team_id']);
        }
        if ($request->exists('is_national')) {
            $data['is_national'] = $this->boolish($request, 'is_national');
        }
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function campData(Request $request, ?SportsCamp $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';
        $data = $request->validate([
            'title_ar' => [$required, 'string', 'max:190'],
            'title_fr' => [$required, 'string', 'max:190'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:190'],
            'notes_ar' => ['nullable', 'string', 'max:4000'],
            'notes_fr' => ['nullable', 'string', 'max:4000'],
            'is_public' => ['sometimes'],
        ]);
        if ($request->exists('is_public')) {
            $data['is_public'] = $this->boolish($request, 'is_public');
        } elseif (! $item) {
            $data['is_public'] = false;
        }

        return $data;
    }

    private function storePhoto(Request $request, object $item, string $column, string $collection): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        $path = StoredFileStore::replace($item->{$column}, $request->file('photo'), $collection)['path'];
        $item->forceFill([$column => $path])->save();
    }
}
