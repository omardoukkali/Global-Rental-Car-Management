<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
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
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'logo_url'      => $this->logo_url,
            'address'       => $this->address,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'status'        => $this->status,
            'avg_rating'    => $this->avg_rating,
            'total_reviews' => $this->total_reviews,
            'created_at'    => $this->created_at,

            // Load relations only if eager loaded
            'owner' => new UserResource($this->whenLoaded('owner')),
            'city'  => new CityResource($this->whenLoaded('city')),
            'cars'  => CarResource::collection($this->whenLoaded('cars')),
        ];
    }
}
