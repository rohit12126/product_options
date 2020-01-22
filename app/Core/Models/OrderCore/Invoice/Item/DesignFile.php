<?php
/**
 * order_core.invoice_item_design_file is an xref table that links an invoice_item with a design file.
 */

namespace App\Core\Models\OrderCore\Invoice\Item;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Traits\HasCompositePrimaryKey;

class DesignFile extends BaseModel
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
        'design_file_id'
    ];

    protected $fillable = [
        'invoice_item_id',
        'design_file_id'
    ];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_item_design_file';

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
    public function designFile()
    {
        return $this->hasOne(\App\Core\Models\OrderCore\DesignFile::class, 'id', 'design_file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productPrint()
    {
        return $this->hasOne(\App\Core\Models\OrderCore\ProductPrintOption::class, 'id', 'product_print_id');
    }
}