<?php
// app/Models/ProductTranslation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'description',
        'seo_title',
        'seo_description',
        'slug',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
