<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'model_run_id', 
    'tanggal', 
    'rayon_id', 
    'rayon_name', 
    'actual_value', 
    'predicted_value', 
    'error_value', 
    'percentage_error'
])]
class PredictionResult extends Model
{
    /**
     * Get the model run that owns this prediction result.
     */
    public function modelRun(): BelongsTo
    {
        return $this->belongsTo(ModelRun::class, 'model_run_id');
    }

    /**
     * Get the rayon associated with this prediction result (if any).
     */
    public function rayon(): BelongsTo
    {
        return $this->belongsTo(Rayon::class, 'rayon_id');
    }
}
