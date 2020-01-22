<?php
namespace App\Core\Models\OrderCore\DataProduct;
use App\Core\Models\BaseModel;

/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/18/16
 * Time: 4:21 PM
 */
class ProductPrice extends BaseModel
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
    protected $table = 'data_product_price';

    public $timestamps = false;

}