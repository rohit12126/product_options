<?php

namespace App\Core\Models\OrderCore;

use Illuminate\Database\Eloquent\Model;
use App\Core\Models\BaseModel;


class Proof extends BaseModel
{
  
    /**
     * Override default
     */
    protected $primaryKey = 'id';

    /**
     * Override default
     *
     * @var bool
     */
    public $incrementing = false;

    public $timestamps = false;

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
    protected $table = 'proof';
}
