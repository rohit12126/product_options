<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 4/17/17
 * Time: 12:28 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;

class PulseOrderType extends BaseModel
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
    protected $table = 'pulse_order_type';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];
}