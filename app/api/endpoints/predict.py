from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from app.models.schemas import PredictionInput, PredictionOutput, DailyPrediction
from app.services.ml_service import ml_service
from app.core.security import get_api_key
from app.core.logger import logger
import pandas as pd
import io
import os
import subprocess
import json

router = APIRouter()

@router.post("/predict", response_model=PredictionOutput, summary="Prediksi Autoregressive (Harian/Bulanan/Tahunan)")
async def predict_revenue(payload: PredictionInput, api_key: str = Depends(get_api_key)):
    try:
        logger.info(f"Menerima request Prediksi dari {payload.tanggal_mulai} sampai {payload.tanggal_akhir}")
        
        # 1. Jalankan Engine Autoregressive Forecast Loop
        prediksi_harian = ml_service.autoregressive_predict(
            start_date_str=payload.tanggal_mulai,
            end_date_str=payload.tanggal_akhir,
            holidays=payload.daftar_libur_nasional
        )
        
        if not prediksi_harian:
            raise ValueError("Tidak ada prediksi yang dihasilkan. Periksa format rentang tanggal.")

        # 2. Rekap dan Hitung Total Pendapatan dari masa/rentang depan
        total_pendapatan = sum(item["pendapatan"] for item in prediksi_harian)
        total_hari = len(prediksi_harian)

        return PredictionOutput(
            status="Sukses",
            pesan=f"Berhasil men-generate ramalan cuan SVR-GWO untuk {total_hari} hari",
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
            kolom_wajib = ['Tanggal', 'Tahun', 'Bulan', 'Tanggal_Kalender', 'Hari_dalam_Minggu', 'Libur_Nasional', 'Total_Pendapatan']
            for col in kolom_wajib:
                if col not in df.columns:
                    raise HTTPException(status_code=400, detail=f"File CSV rusak atau kolom '{col}' tidak ditemukan.")
        except Exception as read_err:
             raise HTTPException(status_code=400, detail=f"Gagal membaca format CSV: {str(read_err)}")

        # Backup/Simpan/Timpa file CSV asli yang ada di direktori root
        file_path = "DATA_PENDAPATAN_PARKIR_PER_HARI_2022-2025.csv"
        with open(file_path, "wb") as f:
            f.write(contents)
            
        logger.info(f"File {file_path} berhasil diperbarui dengan data baru dari Dishub.")
        
        def event_generator():
            try:
                yield f"data: {json.dumps({'type': 'info', 'message': 'Dataset sukses diupload. Memulai Preprocessing & Training...'})}\n\n"
                
                # Memulai proses training di background sinkron (FastAPI pakai threadpool)
                process = subprocess.Popen(
                    ["python", "train.py"],
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    text=True,
                    bufsize=1
                )

                # Membaca output baris demi baris secara otomatis
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
                    logger.error(f"Gagal saat training: {error_log}")
                    yield f"data: {json.dumps({'type': 'error', 'message': 'Terjadi kegagalan saat melatih model awal.'})}\n\n"
                    return
                    
                # Jika sukses, load metrik hasil evaluasi dan pipeline data
                from app.core.config import get_settings
                from app.services.ml_service import ml_service
                ml_service._load_artifacts()
                
                settings = get_settings()
                
                # Load Evaluation Metrics
                eval_path = os.path.join(settings.model_artifacts_dir, "evaluation.json")
                if os.path.exists(eval_path):
                    with open(eval_path, "r") as f:
                        evaluasi_metrik = json.load(f)
                else:
                    evaluasi_metrik = {"pesan": "Metrik belum tersedia"}
                    
                # Load Pipeline Snapshots (Raw & Preprocessed)
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
        
    except HTTPException as httperr:
        raise httperr
    except Exception as e:
        logger.error(f"Error Upload Dataset: {str(e)}")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Terjadi kesalahan saat memproses file CSV: {str(e)}"
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

from fastapi.responses import StreamingResponse
import asyncio

@router.post("/retrain", summary="Latih Ulang Secara Mendalam (Deep GWO Search) secara Streaming")
async def retrain_model(api_key: str = Depends(get_api_key)):
    logger.info("Menjalankan ulang pelatihan GWO secara deep search (Streaming SSE)...")
    
    def event_generator():
        # Memulai proses training di background sinkron dengan Popen
        process = subprocess.Popen(
            ["python", "train.py", "--continue"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            bufsize=1
        )

        # Membaca output baris demi baris secara otomatis
        for line in iter(process.stdout.readline, ''):
            line_str = line.strip()
            if not line_str:
                continue
            
            # Jika itu adalah log Progress GWO, kirim ke Frontend
            if line_str.startswith("[PROGRESS_GWO_"):
                # Ekstrak pesan murni dan persentase untuk klien
                msg = line_str.split("] ")[1]
                yield f"data: {json.dumps({'type': 'progress', 'message': msg})}\n\n"
            else:
                # Log debug lain tetap muncul di backend terminal
                logger.debug(f"Retrain Output: {line_str}")

        process.wait()

        if process.returncode != 0:
            error_log = process.stderr.read()
            logger.error(f"Gagal saat retraining manual: {error_log}")
            yield f"data: {json.dumps({'type': 'error', 'message': 'Terjadi kegagalan saat melatih ulang model.'})}\n\n"
            return
            
        # Jika sukses, load metrik hasil evaluasi ke dalam response
        from app.core.config import get_settings
        from app.services.ml_service import ml_service
        ml_service._load_artifacts()
        
        settings = get_settings()
        eval_path = os.path.join(settings.model_artifacts_dir, "evaluation.json")
        if os.path.exists(eval_path):
            with open(eval_path, "r") as f:
                evaluasi_metrik = json.load(f)
        else:
            evaluasi_metrik = {"pesan": "Metrik belum tersedia"}
            
        # Kirim hasil akhir komparasi ke Klien
        yield f"data: {json.dumps({'type': 'complete', 'komparasi_model': evaluasi_metrik})}\n\n"
        
    # Mengembalikan Streaming Response dengan tipe konten Server-Sent Events
    return StreamingResponse(event_generator(), media_type="text/event-stream")
