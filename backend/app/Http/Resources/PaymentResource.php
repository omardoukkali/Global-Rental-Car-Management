<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'amount'            => $this->amount,
            'commission_amount' => $this->commission_amount,
            'agency_amount'     => $this->agency_amount,
            'payment_method'    => $this->payment_method,
            'status'            => $this->status,
            'paid_at'           => $this->paid_at,
            'released_at'       => $this->released_at,
            'created_at'        => $this->created_at,

            // Relations
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'refund'      => new RefundResource($this->whenLoaded('refund')),
        ];
    }
}
