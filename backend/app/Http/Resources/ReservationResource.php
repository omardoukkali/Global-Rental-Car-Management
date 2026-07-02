<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'reference_number'       => $this->reference_number,
            'start_date'             => $this->start_date,
            'end_date'               => $this->end_date,
            'total_days'             => $this->getTotalDays(),
            'price_per_day_snapshot' => $this->price_per_day_snapshot,
            'total_amount'           => $this->total_amount,
            'commission_amount'      => $this->commission_amount,
            'agency_earning'         => $this->agency_earning,
            'status'                 => $this->status,
            'cancellation_reason'    => $this->cancellation_reason,
            'cancelled_at'           => $this->cancelled_at,
            'completed_at'           => $this->completed_at,
            'created_at'             => $this->created_at,

            // Relations
            'client'  => new UserResource($this->whenLoaded('client')),
            'car'     => new CarResource($this->whenLoaded('car')),
            'agency'  => new AgencyResource($this->whenLoaded('agency')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'review'  => new ReviewResource($this->whenLoaded('review')),
        ];
    }
}
