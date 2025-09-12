<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'avatar' => asset('storage/avatars/' . $this->avatar),
            'phone' => $this->phone,
            'dob' => $this->dob,
            'dob_format' => Carbon::parse($this->dob)->format('mm-dd-yyyy'),
            'is_active' => $this->is_active,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->roles), // Assuming roles relationship is defined in User model
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_format' => Carbon::parse($this->created_at)->format('mm-dd-yyyy'),
            'updated_at_format' => Carbon::parse($this->updated_at)->format('mm-dd-yyyy'),
        ];
    }
}
