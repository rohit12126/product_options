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

class SiteData extends BaseModel
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
    protected $table = 'site_data';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

}