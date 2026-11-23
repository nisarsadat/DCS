<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryDetailResource extends JsonResource
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
            'laboratoryId' => $this->laboratory_id,
            'toothId' => $this->tooth?->id,
            'name' => $this->tooth?->name,
            'toothName' => $this->tooth?->name,
            'images' => ($this->tooth?->photos ?? collect())->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => Media::url($photo->path),
            ])->values(),
            'cost' => $this->cost,
            'total' => $this->total,
            'quantity'=>$this->quantity,
        ];
    }
}
