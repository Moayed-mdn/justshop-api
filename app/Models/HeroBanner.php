<?php

namespace App\Models;

use App\Enums\HeroBanner\HeroLinkTargetEnum;
use App\Enums\HeroBanner\HeroVisualTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeroBanner extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'store_id',
        'cat_url',
        'position',
        'visual_type',
        'image_path',
        'gradient_from',
        'gradient_to',
        'link_url',
        'link_text',
        'link_target',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'visual_type' => HeroVisualTypeEnum::class,
        'link_target' => HeroLinkTargetEnum::class,
        'is_active'   => 'boolean',
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'position'    => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function translations(){

        return $this->hasMany(HeroBannerTranslation::class);
    }

    public function getTranslation($locale = null){
        
        $locale =  $locale ?? app()->getLocale();

        return 
            $this->translations()->firstWhere('locale',$locale) ??
            $this->translations()->firstWhere('locale',config('app.fallback_locale'));
    }

    public function scopeActive($q){

        return $q->where('is_active',true);
    }


    public function getImageUrlAttribute(){
        if (!$this->image_path) {
            return null;
        }
        
        // Use APP_URL for multi-tenant setups to ensure assets are served from backend
        $appUrl = rtrim(config('app.url'), '/');
        return $appUrl . '/storage/' . $this->image_path;
    }

    // public function scopeDate($q){
    //     return $q->where('starts_at','<=',Carbon::now())
    //                 ->where('ends_at','>',Carbon::now());
    // }
}
