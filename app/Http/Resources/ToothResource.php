<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToothResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
           
                "id"=> $this->id,
                "toothId"=> $this->id,
                "name"=> $this->name,
                "quantity"=>1,
                "cost"=>0,
                "total"=>0,
                "description"=> $this->description,
                "images" => $this->photos->map(fn ($photo) => [
                    "id" => $photo->id,
                    "url" => Media::url($photo->path),
                ])->values(),
    
        ];
    }
}
