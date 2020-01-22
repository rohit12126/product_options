<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/13/16
 * Time: 2:35 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice\Shipment;

class Payment extends BaseModel
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
    protected $table = 'payment';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'invoice_shipment_id', 'id');
    }

    /**
     * @param $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }


    /**
     * @return bool
     */
    public function authorize()
    {
        try {
            // ensure latest amount is calculated
            $this->shipment->updatePaymentAmount();
            $this->refresh();
            $payment = Excopy_Payment::instance($this);
        } catch (\Exception $e) {
            (new Log())->logError(
                'frontend',
                $e->getMessage()
            );
            $this->addError($e->getMessage());
            return false;
        }

        if (!$payment->authorize()) {
            $this->addError($payment->getErrors());
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function capture()
    {
        //make sure it's not already captured
        if ($this->status != 'captured') {
            try {
                // ensure latest amount is calculated
                $this->shipment->updatePaymentAmount();
                $this->refresh();
                $payment = Excopy_Payment::instance($this);
            } catch (\Exception $e) {
                (new Log())->logError(
                    'frontend',
                    $e->getMessage()
                );
                $this->addError($e->getMessage());
                return false;
            }
            //capture fund
            if (!$payment->capture()) {
                $this->addError($payment->getErrors());
                return false;
            }
        }
        return true;
    }
}