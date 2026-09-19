# SmartDrive AI — Eligible Vehicles API (SCRUM-173)

This endpoint receives the SmartDrive form ("Votre trajet" + "Votre véhicule"),
applies the rental business rules, and returns **only the vehicles that can really
be rented** for that trip. The AI service then scores and ranks them.

```
Vue form (SCRUM-171/172)
   │  POST /api/smartdrive/eligible-vehicles
   ▼
Laravel (SCRUM-173)  ── returns eligible vehicles + the client's preferences
   │
   ▼
Python AI service (SCRUM-174/175/178)  ── scores, ranks, explains, alternatives
```

---

## 1. Endpoint

| | |
|---|---|
| Method | `POST` |
| URL | `http://localhost:8000/api/smartdrive/eligible-vehicles` |
| Auth | **None** — public, no token needed |
| Headers | `Content-Type: application/json`, `Accept: application/json` |
| Rate limit | 30 requests / minute per IP (then `429`) |

---

## 2. Request body

| Form field (UI) | JSON field | Required | Rules |
|---|---|---|---|
| Budget par jour | `budget_per_day` | yes | number ≥ 0 (MAD) |
| Dates — début | `start_at` | yes | date `YYYY-MM-DD`, today or later |
| Dates — fin | `end_at` | yes | date `YYYY-MM-DD`, after `start_at` |
| Ville de prise en charge | `city_id` | yes | a city `id` from `GET /api/cities` |
| Nombre de passagers | `passengers` | yes | integer 1 – 9 |
| Type de véhicule | `vehicle_type` | no | see mapping below, or `null` |
| Transmission | `transmission` | no | `automatic` / `manual`, or `null` |
| Énergie | `energy_type` | no | `gasoline` / `diesel` / `hybrid` / `electric`, or `null` |

**UI label → API value**

| Type de véhicule | `vehicle_type` | | Énergie | `energy_type` | | Transmission | `transmission` |
|---|---|---|---|---|---|---|---|
| Citadine | `hatchback` | | Essence | `gasoline` | | Automatique | `automatic` |
| Berline | `sedan` | | Diesel | `diesel` | | Manuelle | `manual` |
| SUV | `suv` | | Hybride | `hybrid` | | | |
| Utilitaire | `van` | | Électrique | `electric` | | | |

> There is no "Luxe" type in the database — remove that chip from the form
> (sending `"luxe"` returns a `422`).

### Example

```json
{
  "budget_per_day": 360,
  "start_at": "2026-09-25",
  "end_at": "2026-09-28",
  "city_id": "9f1c2a8e-3b4d-4c5e-8f6a-1b2c3d4e5f60",
  "passengers": 2,
  "vehicle_type": "hatchback",
  "transmission": "automatic",
  "energy_type": "gasoline"
}
```

---

## 3. Which rules filter the vehicles?

A vehicle is returned **only if all of these are true** (hard rules):

1. the car status is `available` (not `unavailable` / `maintenance`)
2. the car is in the chosen pickup city (`city_id`)
3. `seats` ≥ `passengers`
4. the agency is `approved`
5. the agency has an **active pickup point in that city**, and at least one active return point
6. the car has **no pending / confirmed / picked-up reservation** overlapping the dates

**The preferences do NOT filter** — `budget_per_day`, `vehicle_type`,
`transmission` and `energy_type` are sent back in `preferences` so the AI can
**score** the vehicles and propose **alternatives** (e.g. a diesel car slightly over
budget when no gasoline car matches). A car over budget can therefore appear in
the list — it is the AI's job to rank it lower.

---

## 4. Response — `200 OK`

```json
{
  "trip": {
    "city_id": "9f1c2a8e-3b4d-4c5e-8f6a-1b2c3d4e5f60",
    "start_at": "2026-09-25 00:00:00",
    "end_at": "2026-09-28 00:00:00",
    "days": 3,
    "passengers": 2
  },
  "preferences": {
    "budget_per_day": 360,
    "vehicle_type": "hatchback",
    "transmission": "automatic",
    "energy_type": "gasoline"
  },
  "total": 1,
  "vehicles": [
    {
      "id": "a2c8c41f-5f61-434e-a0e3-0a5de3c591fc",
      "brand": "Renault",
      "model": "Clio",
      "year": 2023,
      "color": "Blanc",
      "type": "hatchback",
      "transmission": "automatic",
      "seats": 5,
      "energy_type": "gasoline",
      "fuel_consumption": "5.40",
      "electric_range": null,
      "daily_price": "320.00",
      "total_price": 960,
      "image_url": "https://picsum.photos/seed/.../800/600",
      "agency": {
        "id": "b7e1...",
        "name": "Demo Rent Cars",
        "avg_rating": "4.50",
        "total_reviews": 12
      },
      "pickup_points": [
        { "id": "c1d2...", "name": "Demo Rent Cars - Centre-ville", "address": "12 Rue ..." }
      ],
      "return_points": [
        { "id": "c1d2...", "name": "Demo Rent Cars - Centre-ville", "address": "12 Rue ..." },
        { "id": "e3f4...", "name": "Demo Rent Cars - Aéroport", "address": "..." }
      ]
    }
  ]
}
```

### Vehicle fields

| Field | Type | Notes |
|---|---|---|
| `daily_price` | **string** (`"320.00"`) | Laravel decimal — convert with `float()` / `Number()` |
| `total_price` | number | `daily_price × days` |
| `fuel_consumption` | string or `null` | L/100 km — `null` for electric cars |
| `electric_range` | integer or `null` | km — only electric and hybrid cars |
| `agency.avg_rating` | string or `null` | `null` when the agency has no review yet |
| `image_url` | string or `null` | primary image |
| `pickup_points` / `return_points` | array | ids to use later when creating the reservation (`POST /api/reservations`) |

`days` is rounded up to whole days (minimum 1), like a real reservation.

---

## 5. Errors

| Status | When | Body |
|---|---|---|
| `422` | invalid or missing field | `{ "message": "...", "errors": { "end_at": ["The end at field must be a date after start at."] } }` |
| `429` | more than 30 requests / minute | `{ "message": "Too Many Attempts." }` |
| `200` + `"total": 0` | valid request but no car matches the hard rules | show the empty state (SCRUM-180) |

---

## 6. Frontend usage (SCRUM-172)

Add a service next to the others in `frontend/src/services/`:

```js
// frontend/src/services/smartdrive.js
import api from '@/services/api'

export default {
  getEligibleVehicles(form) {
    return api.post('/smartdrive/eligible-vehicles', {
      budget_per_day: form.budget,
      start_at: form.startDate,       // "2026-09-25"
      end_at: form.endDate,           // "2026-09-28"
      city_id: form.cityId,
      passengers: form.passengers,
      vehicle_type: form.vehicleType,   // "hatchback", "sedan", "suv", "van" or null
      transmission: form.transmission,  // "automatic", "manual" or null
      energy_type: form.energyType,     // "gasoline", "diesel", "hybrid", "electric" or null
    })
  },
}
```

In the page:

```js
try {
  const data = await smartdriveService.getEligibleVehicles(form)
  vehicles.value = data.vehicles        // api.js already returns response.data
  if (data.total === 0) showEmptyState()
} catch (err) {
  // err.errors = validation errors (422), err.message = readable message
  fieldErrors.value = err.errors || {}
}
```

The city dropdown uses `GET /api/cities` → `[{ "id": "...", "name": "Tangier" }, ...]`.

---

## 7. AI service usage (SCRUM-174 / 175 / 178)

- Use the response body as the AI input: `trip` + `preferences` + `vehicles`.
- Every vehicle in the list **is already rentable** — the AI never needs to check
  availability, seats, city or agency status.
- Scoring ideas using the available fields: `daily_price` vs `budget_per_day`,
  `type` / `transmission` / `energy_type` vs the preferences, `agency.avg_rating`,
  `fuel_consumption` / `electric_range`, `year`.
- A vehicle that does not match a preference is a good candidate for
  "alternatives" (SCRUM-177).
- Remember to convert the decimal strings (`daily_price`, `fuel_consumption`,
  `avg_rating`) to numbers.

---

## 8. Try it locally

1. Seed demo data (⚠ wipes the dev database):
   `docker compose exec backend php artisan migrate:fresh --seed`
2. Get a city id: `GET http://localhost:8000/api/cities`
3. Call the endpoint:

```bash
curl -X POST http://localhost:8000/api/smartdrive/eligible-vehicles \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"budget_per_day":360,"start_at":"2026-09-25","end_at":"2026-09-28","city_id":"<city id>","passengers":2,"vehicle_type":"hatchback","transmission":"automatic","energy_type":"gasoline"}'
```

Source: `backend/app/Http/Controllers/SmartDrive/SmartDriveController.php`,
validation in `backend/app/Http/Requests/SmartDrive/SmartDriveRequest.php`.
