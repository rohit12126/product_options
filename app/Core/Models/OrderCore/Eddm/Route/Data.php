<?php
/**
 * order_core.eddm_data is a table for storing non-normalized data for eddm.
 */

namespace App\Core\Models\OrderCore\Eddm\Route;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Data extends BaseModel
{
    use HasCompositePrimaryKey;
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $primaryKey = ['route_id', 'date_created'];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'eddm_route_data';

    public $incrementing = false;
}