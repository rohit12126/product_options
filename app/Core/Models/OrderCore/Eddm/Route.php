<?php

namespace App\Core\Models\OrderCore\Eddm;

use App\Core\Models\BaseModel;

class Route extends BaseModel
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
    protected $table = 'eddm_route';

    public $timestamps = false;
   
}