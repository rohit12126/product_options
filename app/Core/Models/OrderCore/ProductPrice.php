<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use Illuminate\Support\Facades\DB;

class ProductPrice extends BaseModel
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
    protected $table = 'product_price';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * Get the price for a given quantity of postcards.
     *
     * @return mixed
     */
    //TODO - Move this method to the Site model
    public function getProductPricing()
    {
        $pulseSiteId = 2;
        $pricingData = $this->join('order_core.product as p', 'p.id', '=', 'product_price.product_id')
            ->join('order_core.product_print as pp2', 'pp2.id', '=', 'p.product_print_id')
            ->where('site_id', '=', $pulseSiteId)
            ->whereRaw(
                '((order_core.product_price.date_start <= curdate() AND order_core.product_price.date_end >= curdate()) 
                OR (order_core.product_price.date_start <= curdate() AND order_core.product_price.date_end IS NULL))'
            )
            ->distinct()
            ->select('p.*')
            ->get();

        return $pricingData;
    }

    /**
     * Get the price total for a given quantity
     *
     * @param $quantity
     * @return mixed
     */
    public function getPriceByQuantity($quantity)
    {
        $pricingData = $this->where('min_quantity', '>=', $quantity)
            ->orderBy('min_quantity', 'asc')
            ->first();

        return (($pricingData->price + $pricingData->postage_price) * $quantity);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCurrent($query)
    {
        $pricingDate = date('Y-m-d H:i:s', time());
        return $query->where('product_id','!=','0')->where('date_start', '<=', $pricingDate)
            ->whereRaw('(date_end > ? OR date_end IS NULL)', [$pricingDate]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}