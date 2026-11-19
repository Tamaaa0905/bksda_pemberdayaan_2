<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nte extends Model
{
    protected $guarded = [];

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
}
