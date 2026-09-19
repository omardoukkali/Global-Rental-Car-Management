import os
import json
from urllib.request import urlopen
from pathlib import Path

from fastapi import FastAPI
from pydantic import BaseModel, Field
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles
import uvicorn

app = FastAPI()
BASE_DIR = Path(__file__).resolve().parent
BACKEND_URL = os.environ.get("BACKEND_URL", "http://backend:8000/api")


class RecommendationRequest(BaseModel):
    budget: float = Field(default=450, ge=0)
    passengers: int = Field(default=2, ge=1, le=12)
    city: str = ""
    vehicle_type: str = ""
    transmission: str = ""
    energy: str = ""


def score_vehicle(car, request):
    score = 54
    if request.vehicle_type and str(car.get("type", "")).lower() == request.vehicle_type.lower():
        score += 16
    if request.transmission and str(car.get("transmission", "")).lower() == request.transmission.lower():
        score += 13
    if request.energy and str(car.get("energy_type", "")).lower() == request.energy.lower():
        score += 10
    if int(car.get("seats") or 0) >= request.passengers:
        score += 10
    price = float(car.get("daily_price") or 0)
    score += 10 if price <= request.budget else -min(18, int((price - request.budget) / 50) + 1)
    return max(1, min(99, score))


@app.get("/health")
def health():
    return {"status": "ok", "service": "smartdrive-ai"}


@app.get("/cities")
def cities():
    fallback = [{"id": name.lower(), "name": name} for name in ("Casablanca", "Marrakech", "Rabat", "Tanger", "Agadir")]
    try:
        with urlopen(f"{BACKEND_URL}/cities", timeout=2) as response:
            payload = json.loads(response.read().decode("utf-8"))
            if isinstance(payload, list):
                return {"cities": payload}
            return {"cities": payload.get("cities", payload.get("data", []))}
    except Exception:
        return {"cities": fallback}


@app.post("/api/recommend")
def recommend(request: RecommendationRequest):
    fallback = [
        {"id": "sandero", "brand": "Dacia", "model": "Sandero", "type": "Citadine", "seats": 5, "transmission": "Manuelle", "energy_type": "Essence", "daily_price": 260, "rating": 4.7},
        {"id": "clio", "brand": "Renault", "model": "Clio V", "type": "Citadine", "seats": 5, "transmission": "Automatique", "energy_type": "Essence", "daily_price": 340, "rating": 4.9},
        {"id": "duster", "brand": "Dacia", "model": "Duster", "type": "SUV", "seats": 5, "transmission": "Manuelle", "energy_type": "Diesel", "daily_price": 480, "rating": 4.8},
    ]
    results = [{**car, "score": score_vehicle(car, request)} for car in fallback]
    results.sort(key=lambda car: car["score"], reverse=True)
    return {"recommended": results[0], "results": results}

@app.get("/")
def read_root():
    return FileResponse(BASE_DIR / "static" / "index.html")


app.mount("/static", StaticFiles(directory=BASE_DIR / "static"), name="static")

if __name__ == "__main__":
    # Dynamically grab the port from the environment, default to 5000
    port = int(os.environ.get("PORT", 5000))
    uvicorn.run(app, host="0.0.0.0", port=port)