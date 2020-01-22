<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/20/16
 * Time: 10:35 AM
 */

namespace App\Core\Models\OrderCore\Listing;
use App\Core\Models\BaseModel;

class Status extends BaseModel
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
    protected $table = 'listing_status';
}
