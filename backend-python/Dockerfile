FROM python:3.10-slim

WORKDIR /app

# Install system packages required for some python wheels (e.g. scikit-learn)
RUN apt-get update && apt-get install -y \
    build-essential \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements file first to take advantage of docker caching
COPY requirements.txt .

# Install python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of the FastAPI application files
COPY . .

# Expose port 8000 for FastAPI
EXPOSE 8000

# Command to run uvicorn server
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
