<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Member extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'active', 'inactive', 'archived'];

    public const GENDERS = ['male', 'female'];

    public const MEMBERSHIP_TYPES = ['adherent', 'volunteer', 'supporter', 'student', 'family', 'other'];

    /** Rennes Métropole : Rennes puis les 42 communes de banlieue. */
    public const CITIES = [
        'Rennes',
        'Acigné',
        'Bécherel',
        'Betton',
        'Bourgbarré',
        'Brécé',
        'Bruz',
        'Cesson-Sévigné',
        'Chantepie',
        'Chartres-de-Bretagne',
        'Chavagne',
        'Chevaigné',
        'Cintré',
        'Clayes',
        'Corps-Nuds',
        'Gévezé',
        'L’Hermitage',
        'La Chapelle-Chaussée',
        'La Chapelle-des-Fougeretz',
        'La Chapelle-Thouarault',
        'Laillé',
        'Langan',
        'Le Rheu',
        'Le Verger',
        'Miniac-sous-Bécherel',
        'Montgermont',
        'Mordelles',
        'Nouvoitou',
        'Noyal-Châtillon-sur-Seiche',
        'Orgères',
        'Pacé',
        'Parthenay-de-Bretagne',
        'Pont-Péan',
        'Romillé',
        'Saint-Armel',
        'Saint-Erblon',
        'Saint-Gilles',
        'Saint-Grégoire',
        'Saint-Jacques-de-la-Lande',
        'Saint-Sulpice-la-Forêt',
        'Thorigné-Fouillard',
        'Vern-sur-Seiche',
        'Vezin-le-Coquet',
    ];

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'membership_type',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeFiltered(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('search'), function ($inner) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $inner->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('city', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->string('gender')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->when($request->filled('membership_type'), fn ($q) => $q->where('membership_type', $request->string('membership_type')))
            ->when($request->filled('age_min'), function ($q) use ($request) {
                $min = $request->integer('age_min');
                $q->whereNotNull('birth_date')
                    ->whereDate('birth_date', '<=', now()->subYears($min)->toDateString());
            })
            ->when($request->filled('age_max'), function ($q) use ($request) {
                $max = $request->integer('age_max');
                $q->whereNotNull('birth_date')
                    ->whereDate('birth_date', '>', now()->subYears($max + 1)->toDateString());
            });
    }

    public static function extraCityNames(): array
    {
        if (! Schema::hasTable('member_cities')) {
            return [];
        }

        return MemberCity::query()->orderBy('name')->pluck('name')->all();
    }

    public static function allowedCities(): array
    {
        return array_values(array_unique([...self::CITIES, ...self::extraCityNames()]));
    }

    public static function normalizeCityName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    public static function cityAlreadyExists(string $name): bool
    {
        $needle = mb_strtolower(self::normalizeCityName($name));
        foreach (self::allowedCities() as $existing) {
            if (mb_strtolower((string) $existing) === $needle) {
                return true;
            }
        }

        return false;
    }
}
