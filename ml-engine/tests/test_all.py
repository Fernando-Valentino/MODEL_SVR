"""
Tests untuk Backend FastAPI SVR+GWO Prediksi Retribusi Parkir
=============================================================
Jalankan dengan:  pytest tests/ -v
"""

import pytest
import numpy as np
import pandas as pd
from unittest.mock import patch, MagicMock
from fastapi.testclient import TestClient

from main import app

client = TestClient(app)

# ─────────────────────────────────────────────────────────────
# FIXTURES
# ─────────────────────────────────────────────────────────────

@pytest.fixture
def valid_jwt_token():
    """JWT token valid dengan masa aktif 6 jam."""
    import jwt
    from datetime import datetime, timedelta, timezone
    from app.core.config import get_settings
    settings = get_settings()
    payload = {
        "sub": "test_user",
        "role": "operator",
        "exp": (datetime.now(timezone.utc) + timedelta(hours=6)).timestamp()
    }
    return jwt.encode(payload, settings.jwt_secret, algorithm=settings.jwt_algorithm)


@pytest.fixture
def auth_header(valid_jwt_token):
    return {"Authorization": f"Bearer {valid_jwt_token}"}


@pytest.fixture
def mock_ml_service_ready():
    """Mock MLService yang sudah terlatih (model tidak None)."""
    with patch("app.api.endpoints.predict.ml_service") as mock:
        mock.model = MagicMock()
        mock.scaler_X = MagicMock()
        mock.scaler_y = MagicMock()
        mock.autoregressive_predict.return_value = [
            {"tanggal": "2026-07-01", "pendapatan": 8_500_000.0},
            {"tanggal": "2026-07-02", "pendapatan": 7_900_000.0},
            {"tanggal": "2026-07-03", "pendapatan": 9_100_000.0},
        ]
        yield mock


# ─────────────────────────────────────────────────────────────
# 1. TEST ROOT & HEALTH CHECK
# ─────────────────────────────────────────────────────────────

class TestRootEndpoint:
    def test_root_mengembalikan_status_aktif(self):
        response = client.get("/")
        assert response.status_code == 200
        data = response.json()
        assert "message" in data
        assert "Aktif" in data["message"]

    def test_root_tidak_membutuhkan_auth(self):
        response = client.get("/")
        assert response.status_code == 200


# ─────────────────────────────────────────────────────────────
# 2. TEST AUTENTIKASI API KEY
# ─────────────────────────────────────────────────────────────

class TestJwtAuthentication:
    def test_tanpa_jwt_token_mendapatkan_401(self):
        response = client.post("/api/v1/predict", json={
            "tanggal_mulai": "2026-07-01",
            "tanggal_akhir": "2026-07-03",
        })
        assert response.status_code == 401

    def test_jwt_token_salah_mendapatkan_401(self, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers={"Authorization": "Bearer token-salah"},
        )
        assert response.status_code == 401

    def test_jwt_token_valid_dapat_akses(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers=auth_header,
        )
        assert response.status_code == 200


# ─────────────────────────────────────────────────────────────
# 3. TEST ENDPOINT PREDICT
# ─────────────────────────────────────────────────────────────

class TestPredictEndpoint:

    def test_prediksi_berhasil_mengembalikan_struktur_yang_benar(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers=auth_header,
        )
        assert response.status_code == 200
        data = response.json()

        assert data["status"] == "Sukses"
        assert "pesan" in data
        assert "total_hari_prediksi" in data
        assert "estimasi_total_pendapatan" in data
        assert "detail_harian" in data

    def test_prediksi_mengembalikan_jumlah_hari_yang_benar(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers=auth_header,
        )
        data = response.json()
        assert data["total_hari_prediksi"] == 3
        assert len(data["detail_harian"]) == 3

    def test_prediksi_menghitung_total_pendapatan_dengan_benar(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers=auth_header,
        )
        data = response.json()
        expected_total = 8_500_000.0 + 7_900_000.0 + 9_100_000.0
        assert data["estimasi_total_pendapatan"] == pytest.approx(expected_total, rel=1e-3)

    def test_prediksi_detail_harian_memiliki_field_tanggal_dan_pendapatan(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
            headers=auth_header,
        )
        data = response.json()
        for item in data["detail_harian"]:
            assert "tanggal" in item
            assert "pendapatan" in item
            assert isinstance(item["pendapatan"], (int, float))

    def test_prediksi_dengan_filter_rayon_spesifik(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={
                "tanggal_mulai": "2026-07-01",
                "tanggal_akhir": "2026-07-03",
                "rayon_id": 2,
            },
            headers=auth_header,
        )
        assert response.status_code == 200
        mock_ml_service_ready.autoregressive_predict.assert_called_once_with(
            start_date_str="2026-07-01",
            end_date_str="2026-07-03",
            holidays=[],
            rayon_id=2,
        )

    def test_prediksi_dengan_daftar_libur_nasional(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={
                "tanggal_mulai": "2026-07-01",
                "tanggal_akhir": "2026-07-03",
                "daftar_libur_nasional": ["2026-07-02"],
            },
            headers=auth_header,
        )
        assert response.status_code == 200
        mock_ml_service_ready.autoregressive_predict.assert_called_once_with(
            start_date_str="2026-07-01",
            end_date_str="2026-07-03",
            holidays=["2026-07-02"],
            rayon_id=0,
        )

    def test_prediksi_gagal_jika_model_belum_dilatih(self, auth_header):
        with patch("app.api.endpoints.predict.ml_service") as mock:
            mock.autoregressive_predict.side_effect = ValueError(
                "Model artifacts belum di-load."
            )
            response = client.post(
                "/api/v1/predict",
                json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-03"},
                headers=auth_header,
            )
        assert response.status_code == 503

    def test_prediksi_gagal_jika_hasil_kosong(self, auth_header):
        with patch("app.api.endpoints.predict.ml_service") as mock:
            mock.autoregressive_predict.return_value = []
            response = client.post(
                "/api/v1/predict",
                json={"tanggal_mulai": "2026-07-01", "tanggal_akhir": "2026-07-01"},
                headers=auth_header,
            )
        assert response.status_code in (422, 503)

    def test_prediksi_gagal_jika_payload_tidak_lengkap(self, auth_header):
        response = client.post(
            "/api/v1/predict",
            json={"tanggal_mulai": "2026-07-01"},  # tanggal_akhir hilang
            headers=auth_header,
        )
        assert response.status_code == 422

    def test_rayon_id_di_luar_range_ditolak(self, auth_header, mock_ml_service_ready):
        response = client.post(
            "/api/v1/predict",
            json={
                "tanggal_mulai": "2026-07-01",
                "tanggal_akhir": "2026-07-03",
                "rayon_id": 99,  # tidak valid, harus 0-5
            },
            headers=auth_header,
        )
        assert response.status_code == 422


# ─────────────────────────────────────────────────────────────
# 4. TEST ENDPOINT UPLOAD DATASET
# ─────────────────────────────────────────────────────────────

class TestUploadDatasetEndpoint:

    def test_upload_file_bukan_csv_ditolak(self, auth_header):
        fake_xlsx = b"PK\x03\x04fake excel content"
        response = client.post(
            "/api/v1/upload-dataset",
            headers=auth_header,
            files={"file": ("data.xlsx", fake_xlsx, "application/vnd.ms-excel")},
        )
        assert response.status_code == 400

    def test_upload_csv_tanpa_kolom_wajib_ditolak(self, auth_header):
        bad_csv = b"KolumSalah,KolumLain\n1,2\n3,4\n"
        response = client.post(
            "/api/v1/upload-dataset",
            headers=auth_header,
            files={"file": ("data.csv", bad_csv, "text/csv")},
        )
        assert response.status_code == 400

    def test_upload_tanpa_jwt_token_ditolak(self):
        csv_data = b"Tanggal,Rayon,Weekend,Jumlah Jukir,Total_Pendapatan\n2025-01-01,1,0,80,1200000\n"
        response = client.post(
            "/api/v1/upload-dataset",
            files={"file": ("data.csv", csv_data, "text/csv")},
        )
        assert response.status_code == 401


# ─────────────────────────────────────────────────────────────
# 5. TEST ENDPOINT LOAD-EXISTING
# ─────────────────────────────────────────────────────────────

class TestLoadExistingEndpoint:

    def test_load_existing_berhasil_jika_artifacts_ada(self, auth_header):
        fake_eval = {"SVR_GWO": {"MAPE": "12.96%", "Akurasi": "87.04%", "R2": 0.911}}
        fake_pipeline = {"raw_data": [], "preprocessed_data": []}

        with (
            patch("app.api.endpoints.predict.ml_service") as mock_svc,
            patch("os.path.exists", return_value=True),
            patch(
                "builtins.open",
                side_effect=[
                    MagicMock(__enter__=lambda s: s, __exit__=MagicMock(return_value=False),
                              read=MagicMock(return_value="")),
                    MagicMock(__enter__=lambda s: s, __exit__=MagicMock(return_value=False),
                              read=MagicMock(return_value="")),
                ],
            ),
            patch("json.load", side_effect=[fake_eval, fake_pipeline]),
        ):
            mock_svc._load_artifacts.return_value = None
            response = client.get("/api/v1/load-existing", headers=auth_header)

        assert response.status_code == 200
        data = response.json()
        assert data["status"] == "Sukses"

    def test_load_existing_gagal_jika_artifacts_tidak_ada(self, auth_header):
        with (
            patch("app.api.endpoints.predict.ml_service") as mock_svc,
            patch("os.path.exists", return_value=False),
        ):
            mock_svc._load_artifacts.return_value = None
            response = client.get("/api/v1/load-existing", headers=auth_header)

        assert response.status_code == 404


# ─────────────────────────────────────────────────────────────
# 6. TEST UNIT ML SERVICE (autoregressive_predict)
# ─────────────────────────────────────────────────────────────

class TestMlServiceUnit:
    """Unit test untuk logika internal MLService tanpa file CSV nyata."""

    def test_autoregressive_predict_raise_jika_model_none(self):
        from app.services.ml_service import MLService
        svc = MLService.__new__(MLService)
        svc.model = None
        svc.scaler_X = None
        svc.scaler_y = None
        svc.artifacts_dir = "artifacts/"

        with pytest.raises(ValueError, match="Model artifacts belum di-load"):
            svc.autoregressive_predict("2026-07-01", "2026-07-03", [])

    def test_autoregressive_predict_raise_format_tanggal_salah(self):
        from app.services.ml_service import MLService
        svc = MLService.__new__(MLService)
        svc.model = MagicMock()
        svc.scaler_X = MagicMock()
        svc.scaler_y = MagicMock()
        svc.artifacts_dir = "artifacts/"

        with pytest.raises(ValueError, match="Format tanggal salah"):
            svc.autoregressive_predict("01-07-2026", "03-07-2026", [])

    def test_autoregressive_predict_raise_jika_tanggal_akhir_lebih_awal(self):
        from app.services.ml_service import MLService
        svc = MLService.__new__(MLService)
        svc.model = MagicMock()
        svc.scaler_X = MagicMock()
        svc.scaler_y = MagicMock()
        svc.artifacts_dir = "artifacts/"

        with pytest.raises(ValueError, match="Tanggal akhir tidak boleh mundur"):
            svc.autoregressive_predict("2026-07-10", "2026-07-05", [])


# ─────────────────────────────────────────────────────────────
# 7. TEST UNIT PREPROCESSING
# ─────────────────────────────────────────────────────────────

class TestPreprocessingUnit:

    def _make_history_df(self) -> pd.DataFrame:
        """Buat DataFrame histori minimal untuk keperluan testing fitur."""
        dates = pd.date_range(start="2025-01-01", periods=60, freq="D")
        rows = []
        for d in dates:
            for r in range(1, 6):
                rows.append({
                    "Tanggal": d,
                    "Rayon": r,
                    "Total_Pendapatan": 1_500_000 + r * 100_000,
                    "Libur_Nasional": 0,
                    "Weekend": 1 if d.weekday() >= 5 else 0,
                    "Jumlah Jukir": 80,
                })
        return pd.DataFrame(rows)

    def test_extract_features_mengembalikan_array_25_kolom(self):
        from app.utils.preprocessing import extract_features_for_day
        df = self._make_history_df()
        result = extract_features_for_day("2025-03-10", rayon=1, df_history_override=df)
        assert result.shape == (1, 25)

    def test_extract_features_untuk_hari_libur_nasional(self):
        from app.utils.preprocessing import extract_features_for_day
        from app.core.constants import FITUR_COLS
        df = self._make_history_df()
        # 2025-08-17 adalah hari kemerdekaan (ada di LIBUR_NASIONAL_ID)
        result = extract_features_for_day("2025-08-17", rayon=1, df_history_override=df)
        assert result.shape[1] == 25
        # Kolom 'Libur_Nasional' di FITUR_COLS ada di indeks 8
        libur_idx = FITUR_COLS.index("Libur_Nasional")
        assert result[0, libur_idx] == 1

    def test_extract_features_untuk_hari_weekend(self):
        from app.utils.preprocessing import extract_features_for_day
        from app.core.constants import FITUR_COLS
        df = self._make_history_df()
        # 2025-03-01 adalah Sabtu
        result = extract_features_for_day("2025-03-01", rayon=2, df_history_override=df)
        weekend_idx = FITUR_COLS.index("Weekend")
        assert result[0, weekend_idx] == 1

    def test_extract_features_untuk_hari_kerja(self):
        from app.utils.preprocessing import extract_features_for_day
        from app.core.constants import FITUR_COLS
        df = self._make_history_df()
        # 2025-03-03 adalah Senin
        result = extract_features_for_day("2025-03-03", rayon=3, df_history_override=df)
        weekend_idx = FITUR_COLS.index("Weekend")
        assert result[0, weekend_idx] == 0

    def test_extract_features_one_hot_rayon_benar(self):
        from app.utils.preprocessing import extract_features_for_day
        from app.core.constants import FITUR_COLS
        df = self._make_history_df()
        for rayon_target in range(1, 6):
            result = extract_features_for_day("2025-03-03", rayon=rayon_target, df_history_override=df)
            col_name = f"Rayon_{rayon_target}"
            idx = FITUR_COLS.index(col_name)
            assert result[0, idx] == 1, f"Rayon_{rayon_target} seharusnya bernilai 1"

    def test_extract_features_raise_format_tanggal_salah(self):
        from app.utils.preprocessing import extract_features_for_day
        df = self._make_history_df()
        with pytest.raises(ValueError, match="Format tanggal salah"):
            extract_features_for_day("03-10-2025", rayon=1, df_history_override=df)


# ─────────────────────────────────────────────────────────────
# 8. TEST UNIT SCHEMAS (Pydantic)
# ─────────────────────────────────────────────────────────────

class TestSchemas:

    def test_prediction_input_valid(self):
        from app.models.schemas import PredictionInput
        payload = PredictionInput(
            tanggal_mulai="2026-07-01",
            tanggal_akhir="2026-07-31",
            daftar_libur_nasional=["2026-07-17"],
            rayon_id=0,
        )
        assert payload.tanggal_mulai == "2026-07-01"
        assert payload.rayon_id == 0
        assert "2026-07-17" in payload.daftar_libur_nasional

    def test_prediction_input_rayon_id_default_adalah_0(self):
        from app.models.schemas import PredictionInput
        payload = PredictionInput(tanggal_mulai="2026-07-01", tanggal_akhir="2026-07-03")
        assert payload.rayon_id == 0

    def test_prediction_input_rayon_id_maksimal_5(self):
        from app.models.schemas import PredictionInput
        from pydantic import ValidationError
        with pytest.raises(ValidationError):
            PredictionInput(
                tanggal_mulai="2026-07-01",
                tanggal_akhir="2026-07-03",
                rayon_id=6,  # Melebihi batas
            )

    def test_prediction_input_rayon_id_minimal_0(self):
        from app.models.schemas import PredictionInput
        from pydantic import ValidationError
        with pytest.raises(ValidationError):
            PredictionInput(
                tanggal_mulai="2026-07-01",
                tanggal_akhir="2026-07-03",
                rayon_id=-1,  # Di bawah minimum
            )

    def test_prediction_output_valid(self):
        from app.models.schemas import PredictionOutput, DailyPrediction
        output = PredictionOutput(
            status="Sukses",
            pesan="Prediksi berhasil",
            total_hari_prediksi=3,
            estimasi_total_pendapatan=25_500_000.0,
            detail_harian=[
                DailyPrediction(tanggal="2026-07-01", pendapatan=8_500_000.0),
                DailyPrediction(tanggal="2026-07-02", pendapatan=7_900_000.0),
                DailyPrediction(tanggal="2026-07-03", pendapatan=9_100_000.0),
            ],
        )
        assert output.total_hari_prediksi == 3
        assert len(output.detail_harian) == 3
        assert output.estimasi_total_pendapatan == pytest.approx(25_500_000.0)

    def test_daily_prediction_fields(self):
        from app.models.schemas import DailyPrediction
        item = DailyPrediction(tanggal="2026-07-01", pendapatan=8_500_000.0)
        assert item.tanggal == "2026-07-01"
        assert item.pendapatan == pytest.approx(8_500_000.0)


# ─────────────────────────────────────────────────────────────
# 9. TEST UNIT CONFIG
# ─────────────────────────────────────────────────────────────

class TestConfig:

    def test_settings_memiliki_default_yang_benar(self):
        from app.core.config import Settings
        settings = Settings()
        assert settings.api_port == 8000
        assert settings.environment == "development"
        assert settings.model_artifacts_dir == "artifacts/"

    def test_get_settings_mengembalikan_instance_settings(self):
        from app.core.config import get_settings
        settings = get_settings()
        assert hasattr(settings, "api_key")
        assert hasattr(settings, "api_port")
        assert hasattr(settings, "model_artifacts_dir")


# ─────────────────────────────────────────────────────────────
# 10. TEST UNIT CONSTANTS
# ─────────────────────────────────────────────────────────────

class TestConstants:

    def test_libur_nasional_id_bukan_list_kosong(self):
        from app.core.constants import LIBUR_NASIONAL_ID
        assert len(LIBUR_NASIONAL_ID) > 0

    def test_libur_nasional_id_format_yyyy_mm_dd(self):
        from app.core.constants import LIBUR_NASIONAL_ID
        import re
        pattern = re.compile(r"^\d{4}-\d{2}-\d{2}$")
        for tanggal in LIBUR_NASIONAL_ID:
            assert pattern.match(tanggal), f"Format tanggal tidak valid: {tanggal}"

    def test_jukir_map_memiliki_5_rayon(self):
        from app.core.constants import JUKIR_MAP
        assert len(JUKIR_MAP) == 5
        assert set(JUKIR_MAP.keys()) == {1, 2, 3, 4, 5}

    def test_jukir_map_semua_nilai_positif(self):
        from app.core.constants import JUKIR_MAP
        for rayon, jukir in JUKIR_MAP.items():
            assert jukir > 0, f"Jumlah Jukir Rayon {rayon} harus positif"

    def test_fitur_cols_berjumlah_25(self):
        from app.core.constants import FITUR_COLS
        assert len(FITUR_COLS) == 25

    def test_fitur_cols_mengandung_kolom_rayon_one_hot(self):
        from app.core.constants import FITUR_COLS
        for i in range(1, 6):
            assert f"Rayon_{i}" in FITUR_COLS

    def test_fitur_cols_mengandung_kolom_lag(self):
        from app.core.constants import FITUR_COLS
        assert "Lag_1" in FITUR_COLS
        assert "Lag_7" in FITUR_COLS
        assert "Lag_14" in FITUR_COLS
        assert "Lag_21" in FITUR_COLS
