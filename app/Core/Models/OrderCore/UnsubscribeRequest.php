<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class UnsubscribeRequest extends BaseModel
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
    protected $table = 'unsubscribe_request';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}

