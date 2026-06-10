<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['model_id', 'tanggal', 'nilai_aktual', 'nilai_prediksi'])]
class HasilSvr extends Model
{
    /**
     * Get the model prediction that this result belongs to.
     */
    public function modelPrediksi(): BelongsTo
    {
        return $this->belongsTo(ModelPrediksi::class, 'model_id');
    }
}
