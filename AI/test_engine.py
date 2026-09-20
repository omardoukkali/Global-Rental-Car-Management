import json
import unittest
from pathlib import Path

from main import RecommendationRequest, analyze_vehicles, load_experimental_vehicles


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

    def test_personalized_matching_vehicle_is_ranked_first(self):
        results = analyze_vehicles(self.vehicles, self.request)
        self.assertEqual(results[0]["id"], "experimental-clio-001")
        self.assertEqual(results[0]["score"], 99)
        self.assertGreater(results[0]["score"], results[-1]["score"])
        self.assertEqual(results, analyze_vehicles(self.vehicles, self.request))

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