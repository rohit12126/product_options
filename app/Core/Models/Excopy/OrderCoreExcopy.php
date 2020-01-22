<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\Item;

class OrderCoreExcopy extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'order_core_excopy';
    protected $guarded = [];
    protected $primaryKey = 'id';

    /**
     * Establish a relationship between order_core.invoice and the legacy excopy database.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderCoreInvoice()
    {
        return $this->hasOne(Invoice::class, 'id', 'order_core_invoice_id');
    }

    /**
     * Establish a relationship between order_core.invoice_item and the legacy excopy database.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderCoreInvoiceItem()
    {
        return $this->hasOne(Item::class, 'id', 'order_core_invoice_item_id');
    }
}