<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['nama_rayon', 'kecamatan', 'lokasi', 'karakteristik_area', 'jumlah_juru_parkir'])]
class Rayon extends Model
{
    /**
     * Get the juru parkir associated with the rayon.
     */
    public function juruParkirs(): HasMany
    {
        return $this->hasMany(JuruParkir::class);
    }

    /**
     * Get the pendapatan entries associated with the rayon.
     */
    public function pendapatans(): HasMany
    {
        return $this->hasMany(Pendapatan::class);
    }
}
