from pydantic_settings import BaseSettings
from functools import lru_cache

class Settings(BaseSettings):
    api_key: str = "default-fallback-key"
    jwt_secret: str = "rahasia-jwt-secret-key-123"
    jwt_algorithm: str = "HS256"
    api_host: str = "0.0.0.0"
    api_port: int = 8000
    environment: str = "development"
    model_artifacts_dir: str = "artifacts/"

    class Config:
        env_file = ".env"

@lru_cache()
def get_settings():
    return Settings()
