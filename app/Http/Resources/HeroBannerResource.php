<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;


class HeroBannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $translation = $this->getTranslation();

        return [
            'id' => $this->id,
            'title' => $translation?->title,
            'subtitle' => $translation?->subtitle,
            'cat_text' => $translation?->cta_text,
            'cat_url' => $this->cat_url,
            'position' => $this->position,
            'visual' => $this->visual_type === 'image'
                    ?
                 [
                    'type' => 'image',
                    'img_url' => $this->image_url
                 ]:
                 [
                    'type' => 'gradient',
                    'gradient_from' => $this->gradient_from,
                    'gradient_to' => $this->gradient_to
                 ]
            
        ];
    }

   
}
