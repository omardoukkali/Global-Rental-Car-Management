import json
from pathlib import Path

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler


RANDOM_STATE = 178
DATA_PATH = Path(__file__).with_name("smartdrive_training_5000.csv")
MODEL_PATH = Path(__file__).with_name("model_smartdrive.pkl")
METADATA_PATH = Path(__file__).with_name("model_smartdrive_columns.json")

NUMERIC_FEATURES = [
    "budget_per_day",
    "days",
    "passengers",
    "seats",
    "year",
    "daily_price",
    "fuel_consumption",
    "electric_range",
    "agency_avg_rating",
    "agency_total_reviews",
    "price_ratio",
    "seat_margin",
]

CATEGORICAL_FEATURES = [
    "pref_vehicle_type",
    "pref_transmission",
    "pref_energy_type",
    "vehicle_type",
    "transmission",
    "energy_type",
]

TARGET = "compatibility_score"
INPUT_COLUMNS = NUMERIC_FEATURES + CATEGORICAL_FEATURES


def readable_feature_name(encoded_name):
    feature_name = encoded_name.split("__", 1)[-1]
    for categorical_feature in CATEGORICAL_FEATURES:
        prefix = f"{categorical_feature}_"
        if feature_name.startswith(prefix):
            return f"{categorical_feature}={feature_name[len(prefix):]}"
    return feature_name


def load_training_data():
    if not DATA_PATH.exists():
        raise FileNotFoundError(f"Training data not found: {DATA_PATH}")

    data = pd.read_csv(DATA_PATH)
    data["price_ratio"] = data["daily_price"] / data["budget_per_day"]
    data["seat_margin"] = data["seats"] - data["passengers"]
    required_columns = INPUT_COLUMNS + [TARGET]
    missing_columns = [column for column in required_columns if column not in data.columns]
    if missing_columns:
        raise ValueError(f"Training data is missing required columns: {missing_columns}")

    if data[required_columns].isnull().any().any():
        null_columns = data[required_columns].columns[data[required_columns].isnull().any()].tolist()
        raise ValueError(f"Training data contains missing values in: {null_columns}")

    return data


def build_pipeline():
    preprocessor = ColumnTransformer(
        transformers=[
            ("numeric", StandardScaler(), NUMERIC_FEATURES),
            ("categorical", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL_FEATURES),
        ]
    )
    estimator = RandomForestRegressor(
        n_estimators=300,
        random_state=RANDOM_STATE,
        n_jobs=-1,
        min_samples_leaf=2,
        max_depth=14,
    )
    return Pipeline(
        steps=[
            ("preprocessor", preprocessor),
            ("model", estimator),
        ]
    )


def write_metadata(data):
    """Write the model input contract for inference.

    FastAPI must compute price_ratio = daily_price / budget_per_day and
    seat_margin = seats - passengers using these exact column names before
    calling the pipeline; the pipeline expects both as direct input columns.
    """
    categorical_values = {
        column: sorted(data[column].astype(str).unique().tolist())
        for column in CATEGORICAL_FEATURES
    }
    metadata = {
        "input_columns": INPUT_COLUMNS,
        "numeric_features": NUMERIC_FEATURES,
        "categorical_features": CATEGORICAL_FEATURES,
        "categorical_values": categorical_values,
        "target": TARGET,
    }
    with METADATA_PATH.open("w", encoding="utf-8") as metadata_file:
        json.dump(metadata, metadata_file, indent=2)


def main():
    data = load_training_data()
    x_train, x_test, y_train, y_test = train_test_split(
        data[INPUT_COLUMNS],
        data[TARGET],
        test_size=0.2,
        random_state=RANDOM_STATE,
    )

    pipeline = build_pipeline()
    pipeline.fit(x_train, y_train)

    predictions = pipeline.predict(x_test)
    mae = mean_absolute_error(y_test, predictions)
    r2 = r2_score(y_test, predictions)

    joblib.dump(pipeline, MODEL_PATH)
    write_metadata(data)

    feature_names = pipeline.named_steps["preprocessor"].get_feature_names_out()
    importances = pipeline.named_steps["model"].feature_importances_
    top_features = sorted(
        zip(feature_names, importances), key=lambda item: item[1], reverse=True
    )[:10]

    print(f"Final MAE: {mae:.4f}")
    print(f"Final R²: {r2:.4f}")
    print("Top 10 feature importances:")
    for feature_name, importance in top_features:
        print(f"  {readable_feature_name(feature_name)}: {importance:.6f}")
    print(f"Wrote {MODEL_PATH.name} ({MODEL_PATH.stat().st_size:,} bytes)")
    print(f"Wrote {METADATA_PATH.name} ({METADATA_PATH.stat().st_size:,} bytes)")


if __name__ == "__main__":
    main()