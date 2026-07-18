<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar_url'        => $this->avatar_url,
            'role'              => $this->role,
            'status'            => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,

            // Load agency only if user is agency_owner (and prevent circular recursion)
            'agency' => $this->when(
                $this->role === 'agency_owner',
                function () {
                    if ($this->relationLoaded('agency') && $this->agency) {
                        // Break circular dependency if Laravel auto-assigned the inverse relation
                        if ($this->agency->relationLoaded('owner')) {
                            $agencyClone = clone $this->agency;
                            $agencyClone->unsetRelation('owner');
                            return new AgencyResource($agencyClone);
                        }
                        return new AgencyResource($this->agency);
                    }
                    return null;
                }
            ),
        ];
    }
}
