<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/18/16
 * Time: 4:29 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\LineItem\Price;

class LineItem extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $primaryKey = 'id';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'line_item';

    public $timestamps = false;


    /**
     * Establish a relationship with a line item's price.
     */
    public function price()
    {
        $this->hasMany(Price::class, 'line_item_id', 'id');
    }

    /**
     * Get pricing for a line item.
     *
     * @param null $timestamp
     * @param null $siteId
     * @return mixed
     */
    public function getPricing($timestamp = null, $siteId = null)
    {
        if (is_null($siteId)) {
            $siteId = Site::getPricingSiteId();
        }

        if (is_null($timestamp)) {
            $pricingDate = date('Y-m-d H:i:s', time());
        } else {
            $pricingDate = date('Y-m-d H:i:s', $timestamp);
        }

        return $this->price()->where('date_start', '<=', $pricingDate)
            ->whereRaw('date_end > ? OR date_end IS NULL', [$pricingDate])
            ->where('site_id', $siteId);

    }

}