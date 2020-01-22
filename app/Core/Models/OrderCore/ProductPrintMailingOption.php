<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/10/17
 * Time: 3:47 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class ProductPrintMailingOption extends BaseModel
{
    use HasCompositePrimaryKey;
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
    protected $table = 'product_print_mailing_option';


    protected $primaryKey = ['product_print_id', 'mailing_option_id'];

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

}