import os
import json
from urllib.request import Request, urlopen
from pathlib import Path
from typing import Optional

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles
import uvicorn

app = FastAPI()
BASE_DIR = Path(__file__).resolve().parent
BACKEND_URL = os.environ.get("BACKEND_URL", "http://backend:8000/api")
EXPERIMENTAL_DATA_PATH = BASE_DIR / "data" / "experimental_vehicles.json"


class RecommendationRequest(BaseModel):
    budget_per_day: float = Field(ge=0)
    start_at: str
    end_at: str
    city_id: str
    passengers: int = Field(ge=1, le=9)
    vehicle_type: Optional[str] = None
    transmission: Optional[str] = None
    energy_type: Optional[str] = None


def score_vehicle(car, request):
    score = 40
    matched_preferences = 0
    preference_count = 0
    if request.vehicle_type and str(car.get("type", "")).lower() == request.vehicle_type.lower():
        score += 18
        matched_preferences += 1
    if request.vehicle_type:
        preference_count += 1
    if request.transmission and str(car.get("transmission", "")).lower() == request.transmission.lower():
        score += 14
        matched_preferences += 1
    if request.transmission:
        preference_count += 1
    if request.energy_type and str(car.get("energy_type", "")).lower() == request.energy_type.lower():
        score += 14
        matched_preferences += 1
    if request.energy_type:
        preference_count += 1
    if int(car.get("seats") or 0) >= request.passengers:
        score += 12
    price = float(car.get("daily_price") or 0)
    score += 20 if price <= request.budget_per_day else -min(20, int((price - request.budget_per_day) / 50) + 1)
    if preference_count and matched_preferences == preference_count:
        score += 5
    return max(1, min(99, round(score)))


def load_experimental_vehicles():
    with EXPERIMENTAL_DATA_PATH.open(encoding="utf-8") as data_file:
        return json.load(data_file)


def analyze_vehicles(vehicles, request):
    analyzed = [{**vehicle, "score": score_vehicle(vehicle, request)} for vehicle in vehicles]
    return sorted(analyzed, key=lambda vehicle: (-vehicle["score"], -float(vehicle.get("daily_price") or 0)))


def fetch_eligible_vehicles(request):
    request_data = request.model_dump() if hasattr(request, "model_dump") else request.dict()
    payload = json.dumps(request_data).encode("utf-8")
    backend_request = Request(
        f"{BACKEND_URL}/smartdrive/eligible-vehicles",
        data=payload,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST",
    )
    with urlopen(backend_request, timeout=5) as response:
        return json.loads(response.read().decode("utf-8"))


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
    try:
        eligible = fetch_eligible_vehicles(request)
        vehicles = eligible.get("vehicles", [])
        source = "laravel"
    except Exception as error:
        if os.environ.get("SMARTDRIVE_USE_EXPERIMENTAL_DATA", "false").lower() != "true":
            raise HTTPException(status_code=503, detail="Le service de véhicules éligibles est indisponible.") from error
        vehicles = load_experimental_vehicles()
        eligible = {"trip": None, "preferences": request.dict()}
        source = "experimental"

    results = analyze_vehicles(vehicles, request)
    return {
        "trip": eligible.get("trip"),
        "preferences": eligible.get("preferences"),
        "total": len(results),
        "recommended": results[0] if results else None,
        "results": results,
        "source": source,
    }

@app.get("/")
def read_root():
    return FileResponse(BASE_DIR / "static" / "index.html")


app.mount("/static", StaticFiles(directory=BASE_DIR / "static"), name="static")

if __name__ == "__main__":
    # Dynamically grab the port from the environment, default to 5000
    port = int(os.environ.get("PORT", 5000))
    uvicorn.run(app, host="0.0.0.0", port=port)