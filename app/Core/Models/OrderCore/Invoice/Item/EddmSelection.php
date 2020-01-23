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

class EddmSelection extends BaseModel
{
	use HasCompositePrimaryKey;

	/**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $table = 'invoice_item_eddm_selection';

    public $incrementing = false;

    protected $primaryKey = [
        'invoice_item_id',
        'eddm_selection_id'
    ];

    protected $fillable = [
        'invoice_item_id',
        'eddm_selection_id',
    ];
}