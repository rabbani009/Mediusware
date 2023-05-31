<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_one',
        'product_variant_two',
        'product_variant_three',
        'price',
        'stock',
        'product_id',
    ];
    
    public function variant()
        {
            return $this->belongsTo(ProductVariant::class);
        }

      public function variantOne()
        {
            return $this->belongsTo(ProductVariant::class, 'product_variant_one');
        }

        public function variantTwo()
        {
            return $this->belongsTo(ProductVariant::class, 'product_variant_two');
        }

        public function variantThree()
        {
            return $this->belongsTo(ProductVariant::class, 'product_variant_three');
        }
}
