from pydantic_settings import BaseSettings
from functools import lru_cache

class Settings(BaseSettings):
    api_key: str = "default-fallback-key"
    api_host: str = "0.0.0.0"
    api_port: int = 8000
    environment: str = "development"
    model_artifacts_dir: str = "artifacts/"

    class Config:
        env_file = ".env"

@lru_cache()
def get_settings():
    return Settings()
