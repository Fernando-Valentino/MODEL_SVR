<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'model_name', 
    'model_type', 
    'status', 
    'total_rows', 
    'train_rows', 
    'test_rows', 
    'train_period', 
    'test_period', 
    'started_at', 
    'finished_at', 
    'created_by', 
    'error_message'
])]
class ModelRun extends Model
{
    /**
     * Get the parameter details associated with this model run.
     */
    public function modelParameter(): HasOne
    {
        return $this->hasOne(ModelParameter::class, 'model_run_id');
    }

    /**
     * Get the evaluation metrics associated with this model run.
     */
    public function modelMetrics(): HasMany
    {
        return $this->hasMany(ModelMetric::class, 'model_run_id');
    }

    /**
     * Get the prediction results associated with this model run.
     */
    public function predictionResults(): HasMany
    {
        return $this->hasMany(PredictionResult::class, 'model_run_id');
    }
}
