<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class SalesRep extends BaseModel
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
    protected $table = 'sales_rep';

    /**
     * Turn off timestamps
     */
    public $timestamps = false;
}