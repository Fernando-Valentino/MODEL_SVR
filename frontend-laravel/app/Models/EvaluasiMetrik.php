<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['model_id', 'mae', 'rmse', 'mape', 'r2_score'])]
class EvaluasiMetrik extends Model
{
    /**
     * Get the model prediction that this metric belongs to.
     */
    public function modelPrediksi(): BelongsTo
    {
        return $this->belongsTo(ModelPrediksi::class, 'model_id');
    }
}
