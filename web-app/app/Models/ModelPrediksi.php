<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['tanggal_generate', 'metode_optimasi', 'parameter_c', 'parameter_epsilon', 'parameter_gamma'])]
class ModelPrediksi extends Model
{
    /**
     * Get the hasil SVR details.
     */
    public function hasilSvrs(): HasMany
    {
        return $this->hasMany(HasilSvr::class, 'model_id');
    }

    /**
     * Get the evaluasi metrik for this model.
     */
    public function evaluasiMetrik(): HasOne
    {
        return $this->hasOne(EvaluasiMetrik::class, 'model_id');
    }

    /**
     * Get the hasil optimasi details.
     */
    public function hasilOptimasiss(): HasMany
    {
        return $this->hasMany(HasilOptimasi::class, 'model_id');
    }

    /**
     * Get the laporans generated from this model.
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'model_id');
    }
}
