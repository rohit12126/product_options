<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class StockOption extends BaseModel
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
    protected $table = 'stock_option';

    /**
     * Get the available stock options based on a collection of Products.
     *
     * @param $products
     * @return \Illuminate\Support\Collection
     */
    public static function getStockOptions($products)
    {
        $stockOptionIds = $products->map(function ($item, $key) {
            return $item->stock_option_id;
        })->unique(function ($item) {
            return $item;
        });

        return self::whereIn('id', $stockOptionIds)->get();
    }
}