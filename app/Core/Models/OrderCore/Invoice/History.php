<?php

namespace App\Core\Models\OrderCore\Invoice;

use App\Core\Models\BaseModel;
use App\Core\Traits\InsertOnDuplicateKey;

class History extends BaseModel
{
    use InsertOnDuplicateKey;
    public $timestamps = false;
    protected $connection = 'order_core';
    protected $guarded = [];
    protected $primaryKey = 'invoice_id';
    protected $table = 'invoice_history';
}