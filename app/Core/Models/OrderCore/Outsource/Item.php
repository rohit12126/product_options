<?php

namespace App\Core\Models\OrderCore\Outsource;
use App\Core\Models\BaseModel;

class Item extends BaseModel
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
    protected $table = 'outsource_item';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['invoice_item_id'];

    protected $primaryKey = 'invoice_item_id';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1);
    }
}