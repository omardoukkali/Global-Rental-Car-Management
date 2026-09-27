"""SCRUM-181 - end-to-end check of the AI service on the Laravel path."""

import unittest

from fastapi.testclient import TestClient

from main import app

client = TestClient(app)

CITY_ID = "a2c296b8-355a-4455-8bdd-8ab5255f813c"


def vehicle(vid, **overrides):
    base = {
        "id": vid,
        "brand": "Dacia",
        "model": "Duster",
        "year": 2023,
        "type": "suv",
        "transmission": "automatic",
        "seats": 5,
        "energy_type": "diesel",
        "fuel_consumption": 6.0,
        "electric_range": 0,
        "daily_price": 400,
        "total_price": 1600,
        "image_url": None,
        "agency": {"id": "ag-1", "name": "Atlas", "avg_rating": 4.6, "total_reviews": 120},
    }
    base.update(overrides)
    return base


def laravel_payload(vehicles):
    return {
        "budget_per_day": 500,
        "start_at": "2026-09-21 10:00:00",
        "end_at": "2026-09-25 10:00:00",
        "city_id": CITY_ID,
        "passengers": 3,
        "vehicle_type": "suv",
        "transmission": "automatic",
        "energy_type": "diesel",
        "trip": {"city_id": CITY_ID, "days": 4, "passengers": 3},
        "vehicles": vehicles,
    }


class SmartDriveFullFlowTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.vehicles = [
            vehicle("car-match", daily_price=400, type="suv", transmission="automatic", energy_type="diesel"),
            vehicle("car-cheap", brand="Kia", model="Picanto", type="hatchback", seats=4,
                    transmission="manual", energy_type="gasoline", daily_price=240, total_price=960,
                    fuel_consumption=5.0),
            vehicle("car-roomy", brand="Ford", model="Transit", type="van", seats=9,
                    transmission="manual", energy_type="diesel", daily_price=700, total_price=2800,
                    fuel_consumption=8.5),
        ]
        cls.response = client.post("/api/recommend", json=laravel_payload(cls.vehicles))

    def test_health_endpoint_is_up(self):
        health = client.get("/health")
        self.assertEqual(health.status_code, 200)
        self.assertEqual(health.json().get("status"), "ok")

    def test_laravel_path_returns_the_full_recommendation_shape(self):
        self.assertEqual(self.response.status_code, 200)
        body = self.response.json()
        for key in ("trip", "preferences", "total", "recommended", "results", "alternatives", "source"):
            self.assertIn(key, body)
        self.assertEqual(body["source"], "laravel")
        self.assertEqual(body["total"], len(self.vehicles))
        self.assertEqual(body["trip"], {"city_id": CITY_ID, "days": 4, "passengers": 3})
        self.assertNotIn("vehicles", body["preferences"])
        self.assertNotIn("trip", body["preferences"])

    def test_results_are_ranked_and_scores_are_bounded(self):
        results = self.response.json()["results"]
        self.assertEqual(len(results), len(self.vehicles))
        scores = [r["score"] for r in results]
        self.assertEqual(scores, sorted(scores, reverse=True))
        for r in results:
            self.assertGreaterEqual(r["score"], 0)
            self.assertLessEqual(r["score"], 100)
            self.assertIn(r["score_source"], ("ml", "rules_fallback"))
            self.assertEqual(len(set(r["score_breakdown"])), 6)
        self.assertEqual(self.response.json()["recommended"]["id"], results[0]["id"])

    def test_each_result_carries_confidence_and_explanation(self):
        for r in self.response.json()["results"]:
            self.assertGreaterEqual(r["confidence"], 0)
            self.assertLessEqual(r["confidence"], 100)
            explanation = r["explanation"]
            self.assertIn("strengths", explanation)
            self.assertIn("tradeoffs", explanation)
            self.assertTrue(explanation["summary"])

    def test_passthrough_fields_from_laravel_are_preserved(self):
        by_id = {r["id"]: r for r in self.response.json()["results"]}
        self.assertEqual(by_id["car-cheap"]["total_price"], 960)
        self.assertEqual(by_id["car-cheap"]["agency"]["avg_rating"], 4.6)
        self.assertEqual(by_id["car-roomy"]["seats"], 9)

    def test_smart_alternatives_are_returned_from_the_results(self):
        body = self.response.json()
        alternatives = body["alternatives"]
        self.assertIn("best_value", alternatives)
        self.assertIn("most_comfortable", alternatives)
        result_ids = {r["id"] for r in body["results"]}
        self.assertIn(alternatives["best_value"]["id"], result_ids)
        self.assertIn(alternatives["most_comfortable"]["id"], result_ids)
        self.assertEqual(alternatives["best_value"]["id"], "car-cheap")
        best_comfort = alternatives["most_comfortable"]["score_breakdown"]["comfort"]
        for r in body["results"]:
            self.assertLessEqual(r["score_breakdown"]["comfort"], best_comfort)

    def test_single_vehicle_still_produces_a_recommendation(self):
        response = client.post("/api/recommend", json=laravel_payload([vehicle("car-solo")]))
        self.assertEqual(response.status_code, 200)
        body = response.json()
        self.assertEqual(body["total"], 1)
        self.assertEqual(body["recommended"]["id"], "car-solo")

    def test_invalid_passengers_is_rejected_with_422(self):
        payload = laravel_payload([vehicle("car-x")])
        payload["passengers"] = 0
        response = client.post("/api/recommend", json=payload)
        self.assertEqual(response.status_code, 422)


if __name__ == "__main__":
    unittest.main()
