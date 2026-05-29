from pydantic import BaseModel, Field
from typing import List, Optional

class PredictionInput(BaseModel):
    tanggal_mulai: str = Field(..., description="Tanggal mulai prediksi (Format: YYYY-MM-DD)")
    tanggal_akhir: str = Field(..., description="Tanggal akhir prediksi (Format: YYYY-MM-DD). Isi sama dengan tanggal mulai jika hanya ingin prediksi 1 hari. Isi jarak rentang bebas jika ingin per minggu/bulan/tahun.")
    daftar_libur_nasional: List[str] = Field(default=[], description="Daftar tanggal libur nasional dalam rentang waktu tersebut (Format: ['YYYY-MM-DD'])")
    rayon_id: Optional[int] = Field(default=0, description="Filter prediksi per Rayon (1-5). Isi 0 untuk mendapatkan total semua Rayon.", ge=0, le=5)

class DailyPrediction(BaseModel):
    tanggal: str
    pendapatan: float

class PredictionOutput(BaseModel):
    status: str
    pesan: str
    total_hari_prediksi: int
    estimasi_total_pendapatan: float
    detail_harian: List[DailyPrediction]
