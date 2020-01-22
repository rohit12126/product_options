<?php
/**
 * order_core.site_data is a table for storing non-normalized data for an site.
 */

namespace App\Core\Models\OrderCore\Site;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Data extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = ['site_id', 'name'];

    protected $table = 'site_data';

    public $timestamps = false;

    public $incrementing = false;
}