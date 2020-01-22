<?php
/**
 * order_core.user_data is a table for storing non-normalized data for a user.
 */

namespace App\Core\Models\OrderCore\User;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Data extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = ['user_id', 'name'];

    protected $table = 'user_data';

    public $timestamps = false;

    public $incrementing = false;

}