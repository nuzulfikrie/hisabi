<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'note' => $this->note,
            'created_at' => $this->created_at?->format('Y-m-d'),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'category' => $this->brand->category ? [
                        'id' => $this->brand->category->id,
                        'name' => $this->brand->category->name,
                        'type' => $this->brand->category->type,
                        'color' => $this->brand->category->color,
                        'icon' => $this->brand->category->icon,
                    ] : null,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'uuid' => $tag->uuid,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ];
                });
            }),
        ];
    }
}

