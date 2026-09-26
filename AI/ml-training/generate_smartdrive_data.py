import csv
import random
from pathlib import Path


SEED = 177
ROW_COUNT = 5000
OUTPUT_PATH = Path(__file__).with_name("smartdrive_training_5000.csv")

FIELDNAMES = [
    "budget_per_day",
    "days",
    "passengers",
    "pref_vehicle_type",
    "pref_transmission",
    "pref_energy_type",
    "vehicle_type",
    "transmission",
    "energy_type",
    "seats",
    "year",
    "daily_price",
    "fuel_consumption",
    "electric_range",
    "agency_avg_rating",
    "agency_total_reviews",
    "compatibility_score",
]

VEHICLE_TYPES = ("sedan", "suv", "hatchback", "coupe", "van", "truck")
TRANSMISSIONS = ("manual", "automatic")
ENERGY_TYPES = ("gasoline", "diesel", "hybrid", "electric")

TYPE_PROFILES = {
    "hatchback": {"weight": 28, "seats": (5, 5), "price": (180, 390)},
    "sedan": {"weight": 27, "seats": (5, 5), "price": (230, 520)},
    "suv": {"weight": 22, "seats": (5, 7), "price": (330, 780)},
    "van": {"weight": 10, "seats": (7, 9), "price": (450, 950)},
    "coupe": {"weight": 8, "seats": (2, 4), "price": (420, 1100)},
    "truck": {"weight": 5, "seats": (2, 5), "price": (380, 900)},
}

BRANDS = {
    "hatchback": ("Dacia", "Renault", "Toyota", "Peugeot", "Hyundai"),
    "sedan": ("Toyota", "Dacia", "Volkswagen", "Hyundai", "Skoda"),
    "suv": ("Dacia", "Toyota", "Kia", "Hyundai", "Peugeot"),
    "van": ("Renault", "Ford", "Mercedes", "Toyota"),
    "coupe": ("BMW", "Mercedes", "Audi", "Toyota"),
    "truck": ("Ford", "Toyota", "Isuzu", "Mitsubishi"),
}


def weighted_choice(rng, values, weights):
    return rng.choices(values, weights=weights, k=1)[0]


def choose_preference(rng, values, weights, any_probability):
    if rng.random() < any_probability:
        return "any"
    return weighted_choice(rng, values, weights)


def build_vehicle(rng):
    vehicle_type = weighted_choice(
        rng,
        list(VEHICLE_TYPES),
        [TYPE_PROFILES[vehicle_type]["weight"] for vehicle_type in VEHICLE_TYPES],
    )
    profile = TYPE_PROFILES[vehicle_type]
    year = rng.choices(
        range(2015, 2027),
        weights=[1, 1, 2, 3, 4, 5, 6, 7, 8, 8, 7, 6],
        k=1,
    )[0]
    energy_type = weighted_choice(
        rng,
        list(ENERGY_TYPES),
        {"gasoline": 48, "diesel": 24, "hybrid": 16, "electric": 12}.values(),
    )
    transmission = weighted_choice(rng, TRANSMISSIONS, [35, 65])
    seats = rng.randint(profile["seats"][0], profile["seats"][1])
    base_price = rng.uniform(*profile["price"])
    age_adjustment = 1 - max(0, 2026 - year) * 0.025
    energy_adjustment = {"gasoline": 1.0, "diesel": 1.04, "hybrid": 1.12, "electric": 1.22}[energy_type]
    daily_price = round(max(150, base_price * age_adjustment * energy_adjustment + rng.gauss(0, 18)), 2)

    if energy_type == "electric":
        fuel_consumption = 0
        electric_range = int(round(rng.triangular(180, 520, 320)))
    else:
        consumption_ranges = {
            "gasoline": (5.5, 9.8),
            "diesel": (4.3, 7.4),
            "hybrid": (3.4, 6.2),
        }
        fuel_consumption = round(rng.uniform(*consumption_ranges[energy_type]), 2)
        electric_range = 0

    return {
        "vehicle_type": vehicle_type,
        "transmission": transmission,
        "energy_type": energy_type,
        "seats": seats,
        "year": year,
        "daily_price": daily_price,
        "fuel_consumption": fuel_consumption,
        "electric_range": electric_range,
        "agency_avg_rating": round(min(5, max(0, rng.gauss(4.25, 0.48))), 2),
        "agency_total_reviews": int(rng.triangular(3, 1200, 180)),
    }


def preference_score(preference, actual, weight):
    return weight if preference in ("any", None) or preference == actual else 0


def calculate_score(rng, request, vehicle):
    budget = request["budget_per_day"]
    price = vehicle["daily_price"]
    budget_score = 25 if price <= budget else max(0, 25 * (1 - (price - budget) / max(budget, 1)))
    suitability = (
        preference_score(request["pref_vehicle_type"], vehicle["vehicle_type"], 10)
        + preference_score(request["pref_transmission"], vehicle["transmission"], 7)
        + preference_score(request["pref_energy_type"], vehicle["energy_type"], 8)
    )
    comfort = 15 if vehicle["seats"] >= request["passengers"] else 0
    quality = min(10, max(0, (vehicle["year"] - 2018) / 7 * 10))
    agency_quality = min(7, vehicle["agency_avg_rating"] / 5 * 7) + min(
        3, vehicle["agency_total_reviews"] / 20
    )
    if vehicle["energy_type"] == "electric":
        efficiency = min(10, max(0, vehicle["electric_range"] / 50))
    else:
        efficiency = max(0, min(10, 10 - max(0, vehicle["fuel_consumption"] - 4) * 2))

    base_score = budget_score + suitability + comfort + quality + agency_quality + efficiency
    noisy_score = base_score * (1 + rng.uniform(-0.05, 0.05))
    return round(min(100, max(0, noisy_score)), 2)


def build_row(rng):
    vehicle = build_vehicle(rng)
    passengers = rng.choices(range(1, 10), weights=[27, 25, 20, 13, 7, 4, 2, 1, 1], k=1)[0]
    days = rng.choices(range(1, 31), weights=[25, 20, 15, 10, 8, 6, 4, 3, 2, 1] + [1] * 20, k=1)[0]
    budget = round(max(150, vehicle["daily_price"] * rng.uniform(0.78, 1.35) + rng.gauss(0, 35)), 2)
    request = {
        "budget_per_day": budget,
        "passengers": passengers,
        "pref_vehicle_type": choose_preference(
            rng, VEHICLE_TYPES, [28, 27, 22, 8, 10, 5], 0.28
        ),
        "pref_transmission": choose_preference(rng, TRANSMISSIONS, [35, 65], 0.32),
        "pref_energy_type": choose_preference(rng, ENERGY_TYPES, [48, 24, 16, 12], 0.34),
    }
    return {
        "budget_per_day": request["budget_per_day"],
        "days": days,
        "passengers": passengers,
        "pref_vehicle_type": request["pref_vehicle_type"],
        "pref_transmission": request["pref_transmission"],
        "pref_energy_type": request["pref_energy_type"],
        **vehicle,
        "compatibility_score": calculate_score(rng, request, vehicle),
    }


def generate_dataset():
    rng = random.Random(SEED)
    rows = []
    seen = set()
    while len(rows) < ROW_COUNT:
        row = build_row(rng)
        key = tuple(row[field] for field in FIELDNAMES)
        if key not in seen:
            seen.add(key)
            rows.append(row)
    return rows


def main():
    rows = generate_dataset()
    with OUTPUT_PATH.open("w", newline="", encoding="utf-8") as output_file:
        writer = csv.DictWriter(output_file, fieldnames=FIELDNAMES)
        writer.writeheader()
        writer.writerows(rows)

    scores = [row["compatibility_score"] for row in rows]
    print(f"Generated {len(rows)} rows in {OUTPUT_PATH}")
    print(f"Columns: {FIELDNAMES}")
    print(
        "compatibility_score min/mean/max: "
        f"{min(scores):.2f}/{sum(scores) / len(scores):.2f}/{max(scores):.2f}"
    )


if __name__ == "__main__":
    main()