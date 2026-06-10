<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['model_id', 'tanggal_laporan', 'jenis_laporan'])]
class Laporan extends Model
{
    /**
     * Get the model prediction that this report belongs to.
     */
    public function modelPrediksi(): BelongsTo
    {
        return $this->belongsTo(ModelPrediksi::class, 'model_id');
    }
}
