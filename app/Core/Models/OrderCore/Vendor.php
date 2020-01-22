<?php
namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class Vendor extends BaseModel
{
    protected $connection = 'order_core';
    protected $table = 'vendor';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1);
    }
}