<?php
/**
 * order_core.invoice_item_data is a table for storing non-normalized data for an invoice item.
 */

namespace App\Core\Models\OrderCore\Invoice\Item;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Traits\HasCompositePrimaryKey;

class Data extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = ['invoice_item_id', 'name'];

    protected $table = 'invoice_item_data';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceItem()
    {
        return $this->belongsTo(Item::class);
    }
}