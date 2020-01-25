<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Outsource\Product as OutsourceProduct;
use Carbon\Carbon;

class Product extends BaseModel
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
    protected $table = 'product';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function price()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productPrint()
    {
        return $this->hasOne('App\Core\Models\OrderCore\ProductPrintOption', 'id', 'product_print_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mailingOption()
    {
        return $this->hasOne('App\Core\Models\OrderCore\MailingOption', 'id', 'mailing_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function stockOption()
    {
        return $this->hasOne('App\Core\Models\OrderCore\StockOption', 'id','stock_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function colorOption()
    {
        return $this->hasOne('App\Core\Models\OrderCore\ColorOption', 'id','color_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function printOption()
    {
        return $this->hasOne('App\Core\Models\OrderCore\PrintOption', 'id','print_option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function finishOption()
    {
        return $this->hasOne('App\Core\Models\OrderCore\FinishOption', 'id','finish_option_id');
    }

    /**
     * @param $quantity
     * @param null $timestamp
     * @param null $siteId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getPricing($quantity, $timestamp = null, $siteId = null)
    {
        if (is_null($siteId)) {
            $siteId = Site::getPricingSiteId();
        }

        if (!is_null($timestamp)) {
            if ($timestamp instanceof Carbon) {
                $pricingDate = $timestamp->toDateTimeString();
            } else {
                $pricingDate = date('Y-m-d H:i:s', $timestamp);
            }
        } else {
            $pricingDate = date('Y-m-d H:i:s', time());
        }

        return $this->price()->where('date_start', '<=', $pricingDate)
            ->whereRaw('(date_end > ? OR date_end IS NULL)', [$pricingDate])
            ->where('site_id', $siteId)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc')
            ->first();
    }

    /**
     * @return boolean
     */
    public function isDirectMail()
    {
        return ($this->mailingOption->type == 'direct');
    }

    /**
     * Get the minimum required quantity for a given product/site.
     *
     * @param $siteId
     * @return mixed
     */
    public function getMinQuantity($siteId, $pricingDate = null)
    {
        if (null == $pricingDate) {
            $pricingDate = date('Y-m-d H:i:s', time());
        }

        return $this->price()->where('date_start', '<=',$pricingDate)
            ->whereRaw('(date_end > ? OR date_end IS NULL)', [$pricingDate])
            ->where('site_id', $siteId)
            ->orderBy('min_quantity', 'asc')
            ->first()->min_quantity;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function outsourceProduct()
    {
        return $this->hasOne(OutsourceProduct::class);
    }
}