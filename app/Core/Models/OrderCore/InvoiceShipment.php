<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class InvoiceShipment extends BaseModel
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
    protected $table = 'invoice_shipment';

     /**
     * Turn off timestamps
     */
    public $timestamps = false;

    protected $fillable = ['invoice_id'];

}