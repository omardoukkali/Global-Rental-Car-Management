<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'amount'           => $this->amount,
            'cancellation_fee' => $this->cancellation_fee,
            'platform_fee'     => $this->platform_fee,
            'agency_fee'       => $this->agency_fee,
            'reason'           => $this->reason,
            'status'           => $this->status,
            'processed_at'     => $this->processed_at,
            'created_at'       => $this->created_at,

            // Relations
            'payment' => new PaymentResource($this->whenLoaded('payment')),
        ];
    }
}
