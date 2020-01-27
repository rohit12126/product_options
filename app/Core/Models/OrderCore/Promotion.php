<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/18/16
 * Time: 10:35 AM
 */

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Invoice\Item;
use Illuminate\Support\Facades\DB;

class Promotion extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'promotion';


    public $errors = [];


    /**
     * @param \App\Core\Models\OrderCore\Invoice $invoice
     * @param Item|null $item
     * @return bool
     */
    public function isEligible(Invoice $invoice, Item $item = null)
    {
        $result = DB::select('SELECT fn_invoice_promo_eligible(?,?) AS eligible', [$invoice->id, $this->id]);
        
        if (!empty($result) && !is_null($result[0]->eligible)) {
            $this->errors[] = $result[0]->eligible;
            return false;
        }

        if (empty($this->errors)) {
            if (!is_null($item)) {
                return $this->_checkInvoiceItemEligibility($item);
            } else {
                foreach ($invoice->items as $item) {
                    if ($this->_checkInvoiceItemEligibility($item)) {
                        return true;
                    }
                }
                return false;
            }
        }
        return true;
    }

    /**
     * @param \App\Core\Models\OrderCore\Invoice $invoice
     * @return mixed
     */
    public function calculate(Invoice $invoice)
    {
        return DB::statement(
            'CALL sp_invoice_promotion(?)', [$invoice->id]
        );
    }

    /**
     * @param Item $item
     * @return bool
     */
    private function _checkInvoiceItemEligibility(Item $item)
    {
        $result = DB::select('SELECT fn_invoice_item_promo_eligible(?,?) AS eligible', [$item->id,
            $this->id]);
        if (!empty($result) && $result[0]->eligible != null) {
            $this->errors[] = $result[0]->eligible;
            return false;
        }
        return true;
    }

     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tiers(){
        return $this->hasMany('App\Core\Models\OrderCore\Promotion\Tier','promotion_id','id');
    }
}
