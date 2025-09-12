<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            // get from storage/app/public/avatars
            'avatar' => asset('storage/avatars/' . $this->avatar),
            'is_active' => $this->is_active,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->roles), // Assuming roles relationship is defined in User model
            'created_at' => $this->created_at,
        ];

        if($this->token) {
            $data['token'] = $this->token;
        }

        return $data;
    }
}
