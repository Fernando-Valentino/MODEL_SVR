<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['rayon_id', 'jumlah_juru_parkir'])]
class JuruParkir extends Model
{
    /**
     * Get the rayon that this juru parkir belongs to.
     */
    public function rayon(): BelongsTo
    {
        return $this->belongsTo(Rayon::class);
    }

    /**
     * Get the pendapatan entries associated with this juru parkir.
     */
    public function pendapatans(): HasMany
    {
        return $this->hasMany(Pendapatan::class);
    }
}
