<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Outsource\Item as OutsourceItem;

class OutsourceJob extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'outsource_jobs';
    protected $guarded = [];
    protected $primaryKey = 'comp_id';
    public $incrementing = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderCoreExcopy()
    {
        return $this->belongsTo(OrderCoreExcopy::class, 'comp_id', 'comp_id');
    }

    /**
     * @return mixed
     */
    public function orderCoreInvoice()
    {
        return $this->orderCoreExcopy()->first()->orderCoreInvoice;
    }

    /**
     * @return mixed
     */
    public function orderCoreInvoiceItem()
    {
        return $this->orderCoreExcopy()->first()->orderCoreInvoiceItem;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function notes()
    {
        return $this->hasOne(Note::class, 'comp_id', 'comp_id');
    }

    /**
     * Establish a relationship between the outsource job and the outsource item.
     * Used by TRK's Outsource station.
     *
     * @return OutsourceItem | null
     */
    public function getOutsourceItem()
    {
        $oce = OrderCoreExcopy::where('comp_id', $this->comp_id)->first();
        if (null !== $oce) {
            return OutsourceItem::find($oce->order_core_invoice_item_id);
        }

        return null;
    }
}