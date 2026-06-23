<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'model_run_id', 
    'kernel', 
    'c_value', 
    'epsilon_value', 
    'gamma_value'
])]
class ModelParameter extends Model
{
    /**
     * Get the model run that owns this parameter.
     */
    public function modelRun(): BelongsTo
    {
        return $this->belongsTo(ModelRun::class, 'model_run_id');
    }
}
