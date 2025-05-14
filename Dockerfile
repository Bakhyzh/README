FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Run the setup script to organize static files
RUN python setup.py

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"] 