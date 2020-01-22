<?php

namespace App\Core\Models\OrderCore\Eddm\Selection;

use App\Core\Models\BaseModel;

class RouteOrig extends BaseModel
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
    protected $table = 'eddm_selection_route_orig';

    public $timestamps = false;
   
}