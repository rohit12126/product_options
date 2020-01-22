<?php

/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/17/16
 * Time: 2:01 PM
 */

namespace App\Core\Models\OrderCore\Invoice\Item;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\DataProduct;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Traits\HasCompositePrimaryKey;

class AddressFile extends BaseModel
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
        'address_file_id'
    ];

    protected $fillable = [
        'invoice_item_id',
        'address_file_id',
        'count'
    ];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_item_address_file';

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
    public function addressFile()
    {
        return $this->hasOne(\App\Core\Models\OrderCore\AddressFile::class, 'id', 'address_file_id');
    }
}