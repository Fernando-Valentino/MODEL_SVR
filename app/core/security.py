import jwt
from datetime import datetime, timezone
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from app.core.config import get_settings

security_scheme = HTTPBearer(auto_error=False)

def get_jwt_token(credentials: HTTPAuthorizationCredentials = Depends(security_scheme)) -> dict:
    if not credentials:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Missing Authorization Header",
        )
    
    token = credentials.credentials
    settings = get_settings()
    try:
        payload = jwt.decode(
            token, 
            settings.jwt_secret, 
            algorithms=[settings.jwt_algorithm]
        )
        
        if "exp" in payload:
            now = datetime.now(timezone.utc).timestamp()
            if now > payload["exp"]:
                raise HTTPException(
                    status_code=status.HTTP_401_UNAUTHORIZED,
                    detail="Token has expired",
                )
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token has expired",
        )
    except jwt.InvalidTokenError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Could not validate credentials",
        )
