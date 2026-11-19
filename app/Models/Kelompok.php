<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelompok extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data_sumber' => 'array',
            'hasil_usaha' => 'decimal:2',
            'butuh_bantuan_lanjutan' => 'boolean',
            'butuh_pendampingan' => 'boolean',
        ];
    }

    public function kawasan(): BelongsTo
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
