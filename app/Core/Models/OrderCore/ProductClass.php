<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class ProductClass extends BaseModel
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
    protected $table = 'product_class';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return string
     */
    public function singularName ()
    {
        return rtrim($this->name, 's');
    }
}