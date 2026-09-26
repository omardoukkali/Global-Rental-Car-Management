import json
import unittest
from pathlib import Path

from main import SCORING_WEIGHTS, RecommendationRequest, analyze_vehicles, confidence_for, explain_vehicle, load_experimental_vehicles, score_breakdown

CITY_IDS = {
    "Agadir": "a2c296b8-dd35-43da-b3df-52459a2033f6",
    "Casablanca": "a2c296b8-72d9-4163-9b99-74673eb932da",
    "Marrakech": "a2c296b8-aa39-40be-8f10-2c700b3585b5",
    "Rabat": "a2c296b8-9360-4b43-abec-7d2f41ae1260",
    "Tangier": "a2c296b8-355a-4455-8bdd-8ab5255f813c",
}


class SmartDriveEngineTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.vehicles = load_experimental_vehicles()
        cls.request = RecommendationRequest(
            budget_per_day=400,
            start_at="2026-09-21",
            end_at="2026-09-25",
            city_id="experimental-city",
            passengers=2,
            vehicle_type="hatchback",
            transmission="automatic",
            energy_type="gasoline",
        )

    def test_dataset_is_large_and_valid_json(self):
        dataset_path = Path(__file__).parent / "data" / "experimental_vehicles.json"
        self.assertTrue(dataset_path.exists())
        self.assertGreaterEqual(len(self.vehicles), 15)
        self.assertEqual(len({vehicle["id"] for vehicle in self.vehicles}), len(self.vehicles))
        required = {"id", "brand", "model", "year", "type", "transmission", "seats", "energy_type", "daily_price"}
        for vehicle in self.vehicles:
            self.assertTrue(required.issubset(vehicle))
            self.assertGreater(vehicle["seats"], 0)
            self.assertGreaterEqual(vehicle["daily_price"], 0)

    def test_every_supported_city_has_a_distinct_experimental_fleet(self):
        city_fleets = {name: load_experimental_vehicles(city_id) for name, city_id in CITY_IDS.items()}
        self.assertTrue(all(len(fleet) > 0 for fleet in city_fleets.values()))
        self.assertEqual(sum(len(fleet) for fleet in city_fleets.values()), len(self.vehicles))
        self.assertEqual(len({vehicle["id"] for fleet in city_fleets.values() for vehicle in fleet}), len(self.vehicles))

    def test_personalized_matching_vehicle_is_ranked_first(self):
        results = analyze_vehicles(self.vehicles, self.request)
        self.assertEqual(results[0]["id"], "experimental-clio-001")
        self.assertEqual(set(results[0]["score_breakdown"]), set(SCORING_WEIGHTS))
        self.assertEqual(sum(SCORING_WEIGHTS.values()), 100)
        self.assertEqual(results[0]["score"], round(sum(results[0]["score_breakdown"].values())))
        self.assertGreater(results[0]["score"], results[-1]["score"])
        self.assertEqual(results, analyze_vehicles(self.vehicles, self.request))

    def test_all_weighted_dimensions_are_used(self):
        breakdown = score_breakdown(self.vehicles[0], self.request)
        self.assertEqual(set(breakdown), set(SCORING_WEIGHTS))
        self.assertTrue(all(0 <= value <= SCORING_WEIGHTS[key] for key, value in breakdown.items()))

    def test_explanation_contains_strengths_and_tradeoffs(self):
        result = analyze_vehicles(self.vehicles, self.request)[0]
        explanation = explain_vehicle(result, self.request)
        confidence = confidence_for(result)
        self.assertGreaterEqual(confidence, 0)
        self.assertLessEqual(confidence, 100)
        self.assertTrue(explanation["strengths"])
        self.assertIn("tradeoffs", explanation)
        self.assertTrue(explanation["summary"])

    def test_large_passenger_request_prefers_nine_seat_vehicle(self):
        if hasattr(self.request, "model_copy"):
            request = self.request.model_copy(update={"passengers": 9, "vehicle_type": "van", "energy_type": "diesel"})
        else:
            request = self.request.copy(update={"passengers": 9, "vehicle_type": "van", "energy_type": "diesel"})
        results = analyze_vehicles(self.vehicles, request)
        self.assertEqual(results[0]["id"], "experimental-proace-001")
        self.assertGreaterEqual(results[0]["seats"], request.passengers)

    def test_empty_input_returns_empty_recommendations(self):
        self.assertEqual(analyze_vehicles([], self.request), [])


if __name__ == "__main__":
    unittest.main()