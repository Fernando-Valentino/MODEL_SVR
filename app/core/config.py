from pydantic_settings import BaseSettings, SettingsConfigDict
from functools import lru_cache

class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    api_key: str = "default-fallback-key"
    jwt_secret: str = "rahasia-jwt-secret-key-123-dishub-cirebon-32-bytes"
    jwt_algorithm: str = "HS256"
    api_host: str = "0.0.0.0"
    api_port: int = 8000
    environment: str = "development"
    model_artifacts_dir: str = "artifacts/"

@lru_cache()
def get_settings():
    return Settings()
