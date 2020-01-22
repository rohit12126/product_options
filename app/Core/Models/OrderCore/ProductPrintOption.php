<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class ProductPrintOption extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'product_print';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the class that is associated with this.
     *
     * @return mixed
     */
    public function productClass()
    {
        return $this->belongsTo(ProductClass::class, 'product_class_id', 'id');
    }

    /**
     * Get print options based on collection of products.
     * 
     * @param $products
     * @return \Illuminate\Support\Collection
     */
    public static function getProductPrintOptions($products)
    {
        $productPrintIds = $products->map(function ($item, $key) {
            return $item->product_print_id;
        })->unique(function ($item) {
            return $item;
        });

        return self::whereIn('id', $productPrintIds)->get();
    }

    /**
     * Show the product print name.
     * Exclude the dimensions, if applicable.
     *
     * @return mixed
     */
    public function nameWithoutSize()
    {
        if (!strpos($this->name, '(')) {
            return $this->name;
        }
        return collect(explode('(', $this->name))->first();
    }
}