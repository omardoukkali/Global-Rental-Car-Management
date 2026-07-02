<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'brand'         => $this->brand,
            'model'         => $this->model,
            'year'          => $this->year,
            'color'         => $this->color,
            'plate_number'  => $this->plate_number,
            'type'          => $this->type,
            'transmission'  => $this->transmission,
            'seats'         => $this->seats,
            'price_per_day' => $this->price_per_day,
            'description'   => $this->description,
            'status'        => $this->status,
            'avg_rating'    => $this->avg_rating,
            'total_reviews' => $this->total_reviews,
            'created_at'    => $this->created_at,

            // Relations
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'city'   => new CityResource($this->whenLoaded('city')),
            'images' => CarImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
