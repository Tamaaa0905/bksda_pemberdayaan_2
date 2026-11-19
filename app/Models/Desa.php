<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    protected $guarded = [];

    public static function normalizeKecamatan(?string $kecamatan): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($kecamatan ?? '')) ?? '', 'UTF-8');
    }

    protected function casts(): array
    {
        return [
            'data_sumber' => 'array',
        ];
    }

    public function kawasan()
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function kelompoks(): HasMany
    {
        return $this->hasMany(Kelompok::class);
    }

    public function petugas(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
