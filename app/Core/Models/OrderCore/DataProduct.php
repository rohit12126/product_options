<?php
/**
 * A data product is a special kind of product that represents data instead of a physical product.
 * Data products are offered in the form of address lists.
 * Users create criteria for an address list and we then populate that list.
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\DataProduct\ProductPrice as DataProductPrice;

class DataProduct extends BaseModel
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
    protected $table = 'data_product';

    public $timestamps = false;


    /**
     * Establish a relationship to a order_core.data_product_price.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function price()
    {
        return $this->hasMany(DataProductPrice::class, 'data_product_id', 'id');
    }


    /**
     * Get the pricing for a data_product.
     * Pricing for data products, like regular products, can be set on a per-site basis.
     *
     * @param null $timestamp
     * @param null $siteId
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function getPricing($timestamp = null, $siteId = null)
    {
        if (!is_null($timestamp)) {
            $pricingDate = date('Y-m-d H:i:s', $timestamp);
        } else {
            $pricingDate = date('Y-m-d H:i:s', time());
        }

        $mainSiteId = Site::where('name', 'expresscopy.com')->first()->id;
        if ($siteId > 0) {
            $siteIds = array($siteId, $mainSiteId);
        } else {
            $siteIds = array($mainSiteId);
        }

        foreach ($siteIds as $siteId) {
            $dataProductPrice = $this->price()
                ->where('site_id', $siteId)
                ->where('date_start', '<=', $pricingDate)
                ->whereRaw('(date_end > ? OR date_end IS NULL)', [$pricingDate])
                ->first();
            if (!is_null($dataProductPrice)) {
                return $dataProductPrice;
            }
        }
        return null;
    }

}