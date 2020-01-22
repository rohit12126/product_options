<?php
/**
 * order_core.invoice_item_data is a table for storing non-normalized data for an invoice item.
 */

namespace App\Core\Models\OrderCore\Invoice;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Data extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = ['invoice_id', 'name'];

    protected $table = 'invoice_data';

    public $timestamps = false;

    public $incrementing = false;
}