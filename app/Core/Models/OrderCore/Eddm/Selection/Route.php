<?php
/**
 * order_core.api_data is a table for storing non-normalized data for api user.
 */

namespace App\Core\Models\OrderCore\Eddm\Selection;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Route extends BaseModel
{
    use HasCompositePrimaryKey;
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
    protected $table = 'eddm_selection_route';

    protected $primaryKey = ['selection_id', 'route_id'];

    public $incrementing = false;

    public $timestamps = false;  
}