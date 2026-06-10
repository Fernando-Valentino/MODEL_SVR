<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['tanggal', 'rayon_id', 'juru_parkir_id', 'jumlah'])]
class Pendapatan extends Model
{
    /**
     * Get the rayon associated with the pendapatan.
     */
    public function rayon(): BelongsTo
    {
        return $this->belongsTo(Rayon::class);
    }

    /**
     * Get the juru parkir associated with the pendapatan.
     */
    public function juruParkir(): BelongsTo
    {
        return $this->belongsTo(JuruParkir::class);
    }
}
