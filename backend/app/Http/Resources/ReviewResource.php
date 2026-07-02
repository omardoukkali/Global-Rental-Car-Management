<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'car_rating'     => $this->car_rating,
            'agency_rating'  => $this->agency_rating,
            'comment'        => $this->comment,
            'created_at'     => $this->created_at,
            'deleted_at'     => $this->deleted_at,

            // Relations
            'client'      => new UserResource($this->whenLoaded('client')),
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
        ];
    }
}
