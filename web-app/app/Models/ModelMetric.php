<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'model_run_id', 
    'mae', 
    'rmse', 
    'mape', 
    'r2_score', 
    'accuracy', 
    'dataset_type'
])]
class ModelMetric extends Model
{
    /**
     * Get the model run that owns this metric.
     */
    public function modelRun(): BelongsTo
    {
        return $this->belongsTo(ModelRun::class, 'model_run_id');
    }
}
