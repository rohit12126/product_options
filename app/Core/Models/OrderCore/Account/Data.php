<?php
/**
 * order_core.account_data is a table for storing non-normalized data for an account.
 */
namespace App\Core\Models\OrderCore\Account;

use App\Core\Models\BaseModel;

class Data extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Turn off timestamps
     */
    public $timestamps = false;

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'account_data';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}