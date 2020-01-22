<?php
/**
 * order_core.address_file_data is a table for storing non-normalized data for an address file.
 */

namespace App\Core\Models\OrderCore\AddressFile;
use App\Core\Models\BaseModel;


class Data extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $primaryKey = ['address_file_id', 'name'];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'address_file_data';

    public $timestamps = false;

    public $incrementing = false;
}