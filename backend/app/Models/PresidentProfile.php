<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresidentProfile extends Model
{
    public const OFFICE_PRESIDENT = 'president';

    public const OFFICE_VICE_PRESIDENT = 'vice_president';

    public const OFFICES = [
        self::OFFICE_PRESIDENT,
        self::OFFICE_VICE_PRESIDENT,
    ];

    protected $fillable = [
        'office',
        'name_ar',
        'name_fr',
        'photo_path',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public static function current(string $office = self::OFFICE_PRESIDENT): self
    {
        abort_unless(in_array($office, self::OFFICES, true), 404);

        $row = static::query()->where('office', $office)->first();
        if ($row) {
            return $row;
        }

        $legacy = $office === self::OFFICE_PRESIDENT
            ? static::query()->where(function ($query) {
                $query->whereNull('office')->orWhere('office', '');
            })->orderBy('id')->first()
            : null;
        if ($legacy) {
            $legacy->forceFill(['office' => $office])->save();

            return $legacy;
        }

        return static::query()->create([
            'office' => $office,
            'is_public' => true,
        ]);
    }

    public static function visiblePublic(string $office): self
    {
        abort_unless(in_array($office, self::OFFICES, true), 404);

        $row = static::query()->where('office', $office)->first();
        if ($row && $row->is_public) {
            return $row;
        }

        return new static([
            'office' => $office,
            'name_ar' => null,
            'name_fr' => null,
            'photo_path' => null,
            'is_public' => false,
        ]);
    }
}
