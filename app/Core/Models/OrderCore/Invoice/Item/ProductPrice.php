<?php
/**
 * order_core.invoice_item_product_price is an xref table that links an invoice_item to the product_price table.
 * This is used to provide a point in time record of a given product's pricing at the time that the order was created.
 * This gets updated when/if the product is changed.
 */

namespace App\Core\Models\OrderCore\Invoice\Item;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Traits\HasCompositePrimaryKey;

class ProductPrice extends BaseModel
{
    use HasCompositePrimaryKey;

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    public $incrementing = false;

    protected $primaryKey = [
        'invoice_item_id',
        'product_price_id',
        'date_created'
    ];

    protected $fillable = [
        'invoice_item_id',
        'product_price_id',
        'date_created',
        'is_active'
    ];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_item_product_price';

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceItem()
    {
        return $this->belongsTo(Item::class, 'id', 'invoice_item_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productPrice()
    {
        return $this->hasOne(\App\Core\Models\OrderCore\ProductPrice::class, 'id', 'product_price_id');
    }
}