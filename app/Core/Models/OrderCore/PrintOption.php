<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class PrintOption extends BaseModel
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
    protected $table = 'print_option';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];
}