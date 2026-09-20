import os
import json
from urllib.request import Request, urlopen
from pathlib import Path
from typing import Optional

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles
import uvicorn

app = FastAPI()
app.add_middleware(
    CORSMiddleware,
    allow_origins=os.environ.get("SMARTDRIVE_ALLOWED_ORIGINS", "*").split(","),
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)
BASE_DIR = Path(__file__).resolve().parent
BACKEND_URL = os.environ.get("BACKEND_URL", "http://backend:8000/api")
EXPERIMENTAL_DATA_PATH = BASE_DIR / "data" / "experimental_vehicles.json"
SCORING_WEIGHTS = {
    "budget": 25,
    "vehicle_suitability": 25,
    "comfort": 15,
    "vehicle_quality": 15,
    "agency_quality": 10,
    "efficiency": 10,
}


class RecommendationRequest(BaseModel):
    budget_per_day: float = Field(ge=0)
    start_at: str
    end_at: str
    city_id: str
    passengers: int = Field(ge=1, le=9)
    vehicle_type: Optional[str] = None
    transmission: Optional[str] = None
    energy_type: Optional[str] = None


def _preference_score(preference, actual, weight):
    if not preference:
        return weight
    return weight if str(preference).lower() == str(actual or "").lower() else 0


def score_breakdown(car, request):
    price = float(car.get("daily_price") or 0)
    budget_score = SCORING_WEIGHTS["budget"] if price <= request.budget_per_day else max(
        0, SCORING_WEIGHTS["budget"] * (1 - (price - request.budget_per_day) / max(request.budget_per_day, 1))
    )
    suitability = (
        _preference_score(request.vehicle_type, car.get("type"), 10)
        + _preference_score(request.transmission, car.get("transmission"), 7)
        + _preference_score(request.energy_type, car.get("energy_type"), 8)
    )
    seats = int(car.get("seats") or 0)
    comfort = SCORING_WEIGHTS["comfort"] if seats >= request.passengers else 0
    year = int(car.get("year") or 0)
    vehicle_rating = float(car.get("vehicle_rating") or 0)
    quality = min(10, max(0, (year - 2018) / 7 * 10)) + min(5, vehicle_rating)
    agency = car.get("agency") or {}
    agency_rating = float(agency.get("avg_rating") or 0)
    reviews = int(agency.get("total_reviews") or 0)
    agency_quality = min(7, agency_rating / 5 * 7) + min(3, reviews / 20)
    consumption = car.get("fuel_consumption")
    if consumption is not None:
        efficiency = max(0, min(10, 10 - max(0, float(consumption) - 4) * 2))
    elif car.get("electric_range"):
        efficiency = min(10, max(0, float(car["electric_range"]) / 50))
    else:
        efficiency = 5
    return {
        "budget": round(budget_score, 2),
        "vehicle_suitability": round(suitability, 2),
        "comfort": round(comfort, 2),
        "vehicle_quality": round(quality, 2),
        "agency_quality": round(agency_quality, 2),
        "efficiency": round(efficiency, 2),
    }


def score_vehicle(car, request):
    return round(sum(score_breakdown(car, request).values()))


def load_experimental_vehicles(city_id=None):
    with EXPERIMENTAL_DATA_PATH.open(encoding="utf-8") as data_file:
        vehicles = json.load(data_file)
    if not city_id:
        return vehicles
    return [vehicle for vehicle in vehicles if vehicle.get("city_id") == city_id]


def analyze_vehicles(vehicles, request):
    analyzed = []
    for vehicle in vehicles:
        breakdown = score_breakdown(vehicle, request)
        analyzed.append({**vehicle, "score": round(sum(breakdown.values())), "score_breakdown": breakdown})
    return sorted(analyzed, key=lambda vehicle: (-vehicle["score"], -float(vehicle.get("daily_price") or 0)))


def confidence_for(car):
    fields = ("year", "seats", "daily_price", "fuel_consumption", "electric_range", "vehicle_rating")
    agency = car.get("agency") or {}
    present = sum(car.get(field) is not None for field in fields)
    present += sum(agency.get(field) is not None for field in ("avg_rating", "total_reviews"))
    return round(present / (len(fields) + 2) * 100)


def explain_vehicle(car, request):
    breakdown = car["score_breakdown"]
    reasons = []
    tradeoffs = []
    price = float(car.get("daily_price") or 0)
    vehicle_type = str(car.get("type") or "").lower()
    transmission = str(car.get("transmission") or "").lower()
    energy = str(car.get("energy_type") or "").lower()
    if price <= request.budget_per_day:
        reasons.append(f"respecte votre budget de {request.budget_per_day:.0f} MAD par jour")
    else:
        tradeoffs.append(f"dépasse votre budget de {price - request.budget_per_day:.0f} MAD par jour")
    if request.vehicle_type and vehicle_type == request.vehicle_type.lower():
        reasons.append(f"correspond au type de véhicule demandé ({request.vehicle_type})")
    elif request.vehicle_type:
        tradeoffs.append(f"ne correspond pas au type demandé ({request.vehicle_type})")
    if request.transmission and transmission == request.transmission.lower():
        reasons.append(f"possède une boîte {request.transmission}")
    elif request.transmission:
        tradeoffs.append(f"utilise une boîte {car.get('transmission') or 'non renseignée'}")
    if request.energy_type and energy == request.energy_type.lower():
        reasons.append(f"utilise l'énergie {request.energy_type}")
    elif request.energy_type:
        tradeoffs.append(f"utilise une énergie différente ({car.get('energy_type') or 'non renseignée'})")
    if breakdown["comfort"] >= 15:
        reasons.append(f"accueille confortablement {request.passengers} passagers")
    else:
        tradeoffs.append(f"sa capacité de {car.get('seats') or 0} places est inférieure à votre groupe")
    if breakdown["vehicle_quality"] >= 12:
        reasons.append("véhicule récent et bien évalué")
    elif breakdown["vehicle_quality"] < 8:
        tradeoffs.append("qualité ou ancienneté moins favorable")
    if breakdown["agency_quality"] >= 8:
        reasons.append("agence très bien notée par ses clients")
    elif breakdown["agency_quality"] < 5:
        tradeoffs.append("peu d'informations disponibles sur la qualité de l'agence")
    if breakdown["efficiency"] >= 8:
        reasons.append("bonne efficacité énergétique")
    elif breakdown["efficiency"] < 5:
        tradeoffs.append("efficacité énergétique plus faible")
    return {
        "strengths": reasons[:5],
        "tradeoffs": tradeoffs[:3],
        "reasons": reasons[:5],
        "summary": " ; ".join(reasons[:3]) or "alternative compatible avec votre recherche",
    }


def build_alternatives(results):
    if not results:
        return {}
    return {
        "best_value": min(results, key=lambda vehicle: (float(vehicle.get("daily_price") or 0), -vehicle["score"])),
        "most_comfortable": max(results, key=lambda vehicle: (vehicle["score_breakdown"]["comfort"], vehicle["score"])),
    }


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
            return {"cities": payload.get("cities", payload.get("data", payload.get("value", [])))}
    except Exception:
        return {"cities": fallback}


@app.get("/api/recommend")
def recommend_info():
    return {
        "service": "smartdrive-ai",
        "message": "Utilisez POST /api/recommend avec vos préférences de location.",
        "method": "POST",
        "experimental_data": EXPERIMENTAL_DATA_PATH.exists(),
    }


@app.post("/api/recommend")
def recommend(request: RecommendationRequest):
    use_experimental = os.environ.get("SMARTDRIVE_USE_EXPERIMENTAL_DATA", "true").lower() == "true"
    try:
        if use_experimental:
            raise LookupError("experimental mode enabled")
        eligible = fetch_eligible_vehicles(request)
        vehicles = eligible.get("vehicles", [])
        source = "laravel"
    except Exception as error:
        if not use_experimental:
            raise HTTPException(status_code=503, detail="Le service de véhicules éligibles est indisponible.") from error
        vehicles = load_experimental_vehicles(request.city_id)
        request_data = request.model_dump() if hasattr(request, "model_dump") else request.dict()
        eligible = {"trip": None, "preferences": request_data}
        source = "experimental"

    results = analyze_vehicles(vehicles, request)
    for vehicle in results:
        vehicle["confidence"] = confidence_for(vehicle)
        vehicle["explanation"] = explain_vehicle(vehicle, request)
    return {
        "trip": eligible.get("trip"),
        "preferences": eligible.get("preferences"),
        "total": len(results),
        "recommended": results[0] if results else None,
        "results": results,
        "alternatives": build_alternatives(results),
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