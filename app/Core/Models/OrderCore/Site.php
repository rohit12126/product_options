<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Site\Data;
use App\Core\Models\OrderCore\Account;
use stdClass;

class Site extends BaseModel
{
    /**
     * Override default
     */
    protected $primaryKey = 'id';

    /**
     * Override default
     *
     * @var bool
     */
    public $incrementing = false;

    public $timestamps = false;

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
    protected $table = 'site';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the account of site.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get a collection of Products based on site pricing.
     *
     * @return mixed
     */
    public function getProductPricing()
    {
        return $this->currentProducts()->get()->map(function ($item, $key) {
            return $item->product;
        });
    }

    /**
     * Return a list of products that are available on a given site.
     * Product availability is driven by product+product_price+site
     *
     * @return mixed
     */
    public function currentProducts()
    {
        if ($this->base_site_id != 0) {
            return self::find($this->base_site_id)->currentProducts();
        } else {
            return $this->hasMany('App\Core\Models\OrderCore\ProductPrice', 'site_id', 'id')
                ->current()
                ->groupBy('product_id')
                ->with('product');
        }
    }

    /**
     * Get a Site Data model.
     *
     * @param null $name
     * @return mixed
     */
    public function getData($name = null)
    {
        $data = $this->hasMany(Data::class, 'site_id', 'id');
        if (is_null($name)) {
            return $data->get();
        } else {
            return $data->where('name', $name)->first();
        }
    }

    /**
     * @param $user
     * @return stdClass
     */
    public static function getSiteConfigWithPulseFailover($user)
    {
        // Load Expresscopy site config and populate config object
        $siteConfig = [];
        if (!empty($siteData = Site::find(2)->getData()->toArray())) {
            foreach ($siteData as $key => $values) {
                $siteConfig[$values['name']] = $values['value'];
            }
        }

        $userSiteData = $user->account()->parentAccount()->getSiteWithFailOver()->getData()->toArray();
        foreach ($userSiteData as $key => $values) {
            $siteConfig[$values['name']] = $values['value'];
        }

        // If there's another site, overwrite any default config values with those
        $config = config('app.server_config');
        if (isset($config['pulse2'])) {
            if (isset($config['pulse2']['siteId'])) {
                if (!empty($siteData = Site::find($config['pulse2']['siteId'])->getData()->toArray())) {
                    foreach ($siteData as $key => $values) {
                        $siteConfig[$values['name']] = $values['value'];
                    }
                }
            }
        }

        return json_decode(json_encode($siteConfig), false);
    }

    /**
     * @return array
     */
    public function getSiteConfigWithInheritance(){
        // Load Expresscopy site config and populate config object
        $siteConfig = [];
        if (!empty($siteData = Site::find(config('app.server_config.defaultSiteId'))->getData()->toArray())) {
            foreach ($siteData as $key => $values) {
                $siteConfig[$values['name']] = $values['value'];
            }
        }
        if (!empty($siteData = $this->getData()->toArray())) {
            foreach ($siteData as $key => $values) {
                $siteConfig[$values['name']] = $values['value'];
            }
        }
        return $siteConfig;
    }

    /**
     * Return the minimum required quantity for a Pulse product.
     * Used when user has no invoice_item.
     */
    public function getPulseMinPricingQty($productPrintId = null)
    {
        //Since currentProducts actually returns pricing.
        $productPrintId = $productPrintId ?: config('app.server_config.pulse2.defaultProductPrintId');
        $minQty = null;
        $this->currentProducts()->get()->sortBy('min_quantity')->each(function($item, $key) use(&$minQty, $productPrintId) {
            if ($item->product->product_print_id == $productPrintId) {
                $minQty = $item->min_quantity;
                return false;
            }
        });
        return $minQty;
    }


    /**
     * Get the order_core.site_id for a site.
     *
     * @return mixed
     */
    public static function getPricingSiteId()
    {
        $config = config('app.server_config');
        return $config['defaultSiteId'];
    }
}