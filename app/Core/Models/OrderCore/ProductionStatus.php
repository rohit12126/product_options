<?php
namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class ProductionStatus extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'order_core';
    protected $table = 'production_status';
    protected $guarded = [];
    protected $primaryKey = 'id';

    public static function getList()
    {
        return self::pluck('name', 'id');
    }
}