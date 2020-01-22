<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/20/16
 * Time: 1:16 PM
 */

namespace App\Core\Models\OrderCore\Invoice;


use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Promotion extends BaseModel
{
    use HasCompositePrimaryKey;

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    public $incrementing = false;

    protected $primaryKey = ['invoice_id', 'promotion_id'];

    protected $fillable = ['invoice_id', 'promotion_id'];
    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_promotion';

    public $timestamps = false;

    /**
     * @return mixed
     */
    public function promotion()
    {
        return $this->belongsTo(\App\Core\Models\OrderCore\Promotion::class);
    }
}