<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Media;
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
        $role= $this->roles->first();
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phone' => $this->phone,
            'role'=> $role ?[
                'id'=>$role->id,
                'name'=> $role->name
            ] : null,
            'email' => $this->email,
            'status' => (bool) $this->status,
            'twoFactorEnabled' => (bool) $this->two_factor_confirmed_at,
            "profilePicture" => Media::url($this[User::COLUMN_PROFILE_PICTURE] ?? null),
            // 'role' => $this->getRoleNames(),
        'permissions' => $this->getAllPermissions()->pluck('name')
        ];
    }
}
