<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 12/27/16
 * Time: 11:30 AM
 */

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\ShippingOption\Price;

class ShippingOption extends BaseModel
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
    protected $table = 'shipping_option';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * Get failover pricing if the shipping vendor's price quote endpoint is down.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function failOverPricing()
    {
        return $this->hasMany(Price::class, 'shipping_option_id', 'id');
    }
}