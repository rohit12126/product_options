<?php

namespace App\Core\Models\OrderCore\User;

use App\Core\Models\BaseModel;

class Industry extends BaseModel
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
    protected $table = 'user_industry';

    protected $guarded = [];

    public $timestamps = false;
}