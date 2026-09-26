import joblib, json, pandas as pd

model = joblib.load("model_smartdrive.pkl")
with open("model_smartdrive_columns.json") as f:
    contract = json.load(f)

def build_row(budget, price, pax, seats, year, fc, er, rating, reviews,
              pref_type, pref_trans, pref_energy, v_type, trans, energy):
    row = {
        "budget_per_day": budget, "days": 5, "passengers": pax, "seats": seats,
        "year": year, "daily_price": price, "fuel_consumption": fc,
        "electric_range": er, "agency_avg_rating": rating,
        "agency_total_reviews": reviews,
        "pref_vehicle_type": pref_type, "pref_transmission": pref_trans,
        "pref_energy_type": pref_energy, "vehicle_type": v_type,
        "transmission": trans, "energy_type": energy,
    }
    row["price_ratio"] = row["daily_price"] / row["budget_per_day"]
    row["seat_margin"] = row["seats"] - row["passengers"]
    return pd.DataFrame([row])[contract["input_columns"]]

good = build_row(400, 380, 4, 5, 2023, 5.5, 0, 4.5, 120,
                  "sedan", "any", "any", "sedan", "automatic", "gasoline")
bad = build_row(400, 1500, 4, 2, 2023, 5.5, 0, 4.5, 120,
                 "sedan", "any", "any", "truck", "automatic", "gasoline")

g = model.predict(good)[0]
b = model.predict(bad)[0]
print("good match score:", g)
print("bad match score:", b)
print("gap:", g - b)