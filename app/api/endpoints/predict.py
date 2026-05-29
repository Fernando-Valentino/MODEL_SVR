from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from fastapi.responses import StreamingResponse
import asyncio
import pandas as pd
import io
import os
import subprocess
import json

from app.models.schemas import PredictionInput, PredictionOutput, DailyPrediction
from app.services.ml_service import ml_service
from app.core.security import get_api_key
from app.core.logger import logger
from app.core.config import get_settings

router = APIRouter()

def _run_training_stream(continue_search: bool = False, initial_message: str = None):
    def event_generator():
        try:
            if initial_message:
                yield f"data: {json.dumps({'type': 'info', 'message': initial_message})}\n\n"
            
            cmd = ["python", "train.py"]
            if continue_search:
                cmd.append("--continue")
                
            process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                bufsize=1
            )
            
            for line in iter(process.stdout.readline, ''):
                line_str = line.strip()
                if not line_str:
                    continue
                
                if line_str.startswith("[PROGRESS_GWO_"):
                    msg = line_str.split("] ")[1]
                    yield f"data: {json.dumps({'type': 'progress', 'message': msg})}\n\n"
                elif "[INFO] " in line_str:
                    msg = line_str.split("[INFO] ")[1]
                    yield f"data: {json.dumps({'type': 'info', 'message': msg})}\n\n"
                else:
                    logger.debug(f"Train Output: {line_str}")
                    
            process.wait()
            
            if process.returncode != 0:
                error_log = process.stderr.read()
                logger.error(f"Gagal saat training/retraining: {error_log}")
                err_msg = "Terjadi kegagalan saat melatih ulang model." if continue_search else "Terjadi kegagalan saat melatih model awal."
                yield f"data: {json.dumps({'type': 'error', 'message': err_msg})}\n\n"
                return
                
            ml_service._load_artifacts()
            settings = get_settings()
            
            eval_path = os.path.join(settings.model_artifacts_dir, "evaluation.json")
            if os.path.exists(eval_path):
                with open(eval_path, "r") as f:
                    evaluasi_metrik = json.load(f)
            else:
                evaluasi_metrik = {"pesan": "Metrik belum tersedia"}
                
            if continue_search:
                yield f"data: {json.dumps({'type': 'complete', 'komparasi_model': evaluasi_metrik})}\n\n"
            else:
                pipeline_path = os.path.join(settings.model_artifacts_dir, "pipeline_data.json")
                if os.path.exists(pipeline_path):
                    with open(pipeline_path, "r") as f:
                        pipeline_data = json.load(f)
                else:
                    pipeline_data = {"raw_data": [], "preprocessed_data": []}
                yield f"data: {json.dumps({'type': 'complete', 'status': 'Sukses', 'komparasi_model': evaluasi_metrik, 'pipeline_data': pipeline_data})}\n\n"
                
        except Exception as ev_err:
            import traceback
            logger.error(f"Event Loop Error: {traceback.format_exc()}")
            yield f"data: {json.dumps({'type': 'error', 'message': str(ev_err)})}\n\n"

    return StreamingResponse(event_generator(), media_type="text/event-stream")

@router.post("/predict", response_model=PredictionOutput, summary="Prediksi Autoregressive (Harian/Bulanan/Tahunan)")
async def predict_revenue(payload: PredictionInput, api_key: str = Depends(get_api_key)):
    try:
        logger.info(f"Menerima request Prediksi dari {payload.tanggal_mulai} sampai {payload.tanggal_akhir}, Rayon: {payload.rayon_id or 'Semua'}")
        
        # 1. Jalankan Engine Autoregressive Forecast Loop
        prediksi_harian = ml_service.autoregressive_predict(
            start_date_str=payload.tanggal_mulai,
            end_date_str=payload.tanggal_akhir,
            holidays=payload.daftar_libur_nasional,
            rayon_id=payload.rayon_id or 0
        )
        
        if not prediksi_harian:
            raise ValueError("Tidak ada prediksi yang dihasilkan. Periksa format rentang tanggal.")

        # 2. Rekap dan Hitung Total Pendapatan dari masa/rentang depan
        total_pendapatan = sum(item["pendapatan"] for item in prediksi_harian)
        total_hari = len(prediksi_harian)
        
        rayon_label = f"Rayon {payload.rayon_id}" if payload.rayon_id else "Semua Rayon"

        return PredictionOutput(
            status="Sukses",
            pesan=f"Berhasil men-generate ramalan cuan SVR-GWO untuk {total_hari} hari ({rayon_label})",
            total_hari_prediksi=total_hari,
            estimasi_total_pendapatan=total_pendapatan,
            detail_harian=[DailyPrediction(**item) for item in prediksi_harian]
        )
        
    except ValueError as val_err:
        logger.error(f"Validation Error: {str(val_err)}")
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE, 
            detail=str(val_err)
        )
    except Exception as e:
        logger.error(f"Internal Server Error: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Terjadi kesalahan pada saat memproses prediksi autoregressive: " + str(e)
        )

@router.post("/upload-dataset", summary="Upload dataset CSV baru & Retrain Model (SVR+GWO)")
async def upload_dataset(
    file: UploadFile = File(..., description="File CSV Pendapatan Parkir. Format kolom harus sama dengan sebelumnya."), 
    api_key: str = Depends(get_api_key)
):
    try:
        logger.info(f"Menerima upload dataset baru: {file.filename}")
        
        if not file.filename.endswith('.csv'):
            raise HTTPException(status_code=400, detail="File harus berformat .csv")
        
        # Membaca isi file yang di-upload menjadi bytes
        contents = await file.read()
        
        try:
            df = pd.read_csv(io.BytesIO(contents), parse_dates=['Tanggal'])
            kolom_wajib = ['Tanggal', 'Rayon', 'Weekend', 'Jumlah Jukir', 'Total_Pendapatan']
            for col in kolom_wajib:
                if col not in df.columns:
                    raise HTTPException(status_code=400, detail=f"File CSV rusak atau kolom '{col}' tidak ditemukan.")
        except HTTPException as http_err:
            raise http_err
        except Exception as read_err:
             raise HTTPException(status_code=400, detail=f"Gagal membaca format CSV: {str(read_err)}")

        # Backup/Simpan/Timpa file CSV asli yang ada di direktori root
        file_path = "DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv"
        with open(file_path, "wb") as f:
            f.write(contents)
            
        logger.info(f"File {file_path} berhasil diperbarui dengan data baru dari Dishub.")
        
        return _run_training_stream(continue_search=False, initial_message="Dataset sukses diupload. Memulai Preprocessing & Training...")
        
    except HTTPException as httperr:
        raise httperr
    except Exception as e:
        logger.error(f"Error Upload Dataset: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Terjadi kesalahan saat memproses file CSV: {str(e)}"
        )

@router.post("/train-existing", summary="Latih model baru menggunakan file CSV yang sudah ada di server")
async def train_existing(api_key: str = Depends(get_api_key)):
    try:
        file_path = "DATA_PENDAPATAN_PARKIR_PER_HARI_2023-2025.csv"
        if not os.path.exists(file_path):
            raise HTTPException(status_code=404, detail="File CSV historis tidak ditemukan di server.")
            
        logger.info("Memulai pelatihan model dari data CSV yang sudah ada di server.")
        
        return _run_training_stream(continue_search=False, initial_message="Dataset server ditemukan. Memulai Preprocessing & Training...")
        
    except HTTPException as httperr:
        raise httperr
    except Exception as e:
        logger.error(f"Error Train Existing: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Terjadi kesalahan saat memproses training: {str(e)}"
        )

@router.get("/load-existing", summary="Muat Model dan Pipeline Data Terakhir")
async def load_existing_model(api_key: str = Depends(get_api_key)):
    try:
        from app.core.config import get_settings
        from app.services.ml_service import ml_service
        ml_service._load_artifacts()
        settings = get_settings()
        
        eval_path = os.path.join(settings.model_artifacts_dir, "evaluation.json")
        pipeline_path = os.path.join(settings.model_artifacts_dir, "pipeline_data.json")
        
        if not os.path.exists(eval_path) or not os.path.exists(pipeline_path):
            raise HTTPException(status_code=404, detail="Belum ada riwayat model yang dilatih sebelumnya. Silakan upload dataset.")
            
        with open(eval_path, "r") as f:
            evaluasi_metrik = json.load(f)
            
        with open(pipeline_path, "r") as f:
            pipeline_data = json.load(f)
            
        return {
            "status": "Sukses",
            "message": "Model sebelumnya berhasil dimuat dari memori.",
            "komparasi_model": evaluasi_metrik,
            "pipeline_data": pipeline_data
        }
        
    except Exception as e:
        logger.error(f"Error Load Existing Model: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Terjadi kesalahan saat memuat model: {str(e)}"
        )

@router.post("/retrain", summary="Latih Ulang Secara Mendalam (Deep GWO Search) secara Streaming")
async def retrain_model(api_key: str = Depends(get_api_key)):
    logger.info("Menjalankan ulang pelatihan GWO secara deep search (Streaming SSE)...")
    return _run_training_stream(continue_search=True)
