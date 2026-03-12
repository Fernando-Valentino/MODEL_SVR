import uvicorn
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.api.endpoints import predict
from app.core.config import get_settings
from app.core.logger import logger

settings = get_settings()

app = FastAPI(
    title="API Prediksi Retribusi Parkir Dishub Kota Cirebon",
    description="IMPLEMENTASI SUPPORT VECTOR REGRESSION DENGAN OPTIMASI GRID SEARCH DAN GREY WOLF OPTIMIZER UNTUK PREDIKSI PENDAPATAN RETRIBUSI PARKIR PADA DINAS PERHUBUNGAN KOTA CIREBON",
    version="1.0.0"
)

# Konfigurasi CORS (Cross-Origin Resource Sharing) untuk mengizinkan aplikasi Laravel
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], # Silakan batasi di Production (misal: ["http://localhost:8000"])
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Registrasi Router
app.include_router(predict.router, prefix="/api/v1", tags=["Prediction"])

@app.get("/")
def read_root():
    return {"message": "Selamat datang di API Prediksi SVR+GWO - Dinas Perhubungan Kota Cirebon (Status: Aktif)"}

if __name__ == "__main__":
    logger.info(f"Server berjalan di {settings.api_host}:{settings.api_port} di lingkungan {settings.environment}")
    uvicorn.run("main:app", host=settings.api_host, port=settings.api_port, reload=True)
