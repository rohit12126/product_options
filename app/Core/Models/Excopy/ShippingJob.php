<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;

class ShippingJob extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'shipping_jobs';
    protected $guarded = [];
    protected $primaryKey = 'comp_id';
    public $incrementing = false;
}