<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_path' => asset('storage/' . $this->image_path),
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
        ];
    }
}
