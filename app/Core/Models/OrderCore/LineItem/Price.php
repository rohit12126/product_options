<?php

namespace App\Core\Models\LineItem;
use App\Core\Models\BaseModel;

/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/19/16
 * Time: 10:41 AM
 */
class Price extends BaseModel 
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
    protected $table = 'line_item_price';

    public $timestamps = false;
    
}