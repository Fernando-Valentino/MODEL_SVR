<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['model_id', 'metode', 'parameter_c', 'parameter_epsilon', 'parameter_gamma'])]
class HasilOptimasi extends Model
{
    // Custom table name since the migration creates 'hasil_optimasiss' to avoid singular/plural conflicts
    protected $table = 'hasil_optimasiss';

    /**
     * Get the model prediction that this optimization result belongs to.
     */
    public function modelPrediksi(): BelongsTo
    {
        return $this->belongsTo(ModelPrediksi::class, 'model_id');
    }
}
