<?php

/**
 * an invoice_item also belongs to an invoice_shipment. This is done to facilitate items that ship at different dates.
 * invoice_shipment -> invoice_item is a 1 to many rel.
 */

namespace App\Core\Models\OrderCore\Invoice;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice;
use App\Core\Models\OrderCore\Log;
use App\Core\Models\OrderCore\Payment;
use App\Core\Service\Ups as UpsService;
use App\Core\Service\Satori;
use App\Core\Utility\ProductionDate;
use Exception;
use Ups\Entity\Address as UpsAddress;


class Shipment extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Turn off timestamps
     */
    public $timestamps = false;

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'invoice_shipment';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public $isFailOverShippingQuote = false;


    public function items()
    {
        return $this->hasMany(Item::class, 'invoice_shipment_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_shipment_id', 'id');
    }

    public function getShippingQuotes($invoiceItems = array(), $markupPercentage = 0)
    {
        $this->isFailOverShippingQuote = false;

        if (empty($invoiceItems)) {
            // get active shippable items
            $invoiceItems = $this->items()->join('order_core.product as p', 'p.id', '=', 'invoice_item.product_id')
                ->where('invoice_item.status', '!=', 'canceled')
                ->where('p.mailing_option_id', '=', '3') //TODO Extract to config
                ->select('invoice_item.*')
                ->get();
        }

        $boxes = 0;
        $letters = 0;
        $weightBoxes = 0;
        $weightLetters = 0;
        $totalValue = 0;
        $itemCount = count($invoiceItems);
        foreach ($invoiceItems as $invoiceItem) {
            if ($invoiceItem->quantity == 0) {
                continue;
            }
            $product = $invoiceItem->product;
            if (!empty($product->capacity_letter) && $invoiceItem->quantity <= $product->capacity_box) {
                $letters += $invoiceItem->quantity / $product->capacity_letter;
                $weightLetters += $product->unit_weight * $invoiceItem->quantity;
            }
            $boxes += $invoiceItem->quantity / $product->capacity_box;
            $weightBoxes += $product->unit_weight * $invoiceItem->quantity;
            $totalValue += $invoiceItem->total;
        }
        $letterCount = ceil($letters);
        $boxCount = ceil($boxes);

        // return null instead of 0 when there aren't any ship only products
        if (0 == $itemCount || 0 == $weightBoxes) {
            return null;
        }


        $toAddress = new UpsAddress();
//        $toAddress->setAttentionName($invoiceItem->shipping_name);
//        $toAddress->setAddressLine1($invoiceItem->shipping_line1);
//        $toAddress->setAddressLine2($invoiceItem->shipping_line2);
//        $toAddress->setAddressLine3($invoiceItem->shipping_line3);
//        $toAddress->setCity($invoiceItem->shipping_city);
        $toAddress->setStateProvinceCode($invoiceItem->shipping_state);
        $toAddress->setPostalCode($invoiceItem->shipping_zip);
        $toAddress->setCountryCode($invoiceItem->shipping_country);

        $ups = new UpsService();
        $shippingQuotes = $ups->getShippingQuotes
        (
            $toAddress,
            $weightLetters,
            $weightBoxes,
            $letterCount,
            $boxCount,
            $itemCount,
            $markupPercentage
        );

        if ($weightLetters == $weightBoxes && ceil($letterCount) <= ceil($boxCount)) {
            $packageWeight = $weightLetters;
            $packageCount = $letterCount;
        } else {
            $packageWeight = $weightBoxes;
            $packageCount = $boxCount;
        }

        $transitTimes = $ups->getEtas
        (
            $toAddress,
            $packageWeight,
            $packageCount,
            $totalValue,
            ProductionDate::getNextAvailable
            (
                (!is_null($this->date_scheduled) ? strtotime($this->date_scheduled) : time())
            )
        );

        foreach ($transitTimes as $code => $transitTime) {
            $shippingQuotes[$code]['eta'] = $transitTime['eta'];
        }

        return $shippingQuotes;
    }

    /**
     * Query scope for active rows
     *
     * @param $query
     * @return
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function validateShippingAddress(UpsAddress $address = null)
    {
        $ups = new UpsService();
        if (is_null($address)) {
            foreach ($this->items as $item) {
                if ($item->isPrintOnly()) {
                    $address = new UpsAddress();
                    $address->setAttentionName($item->shipping_name);
                    $address->setAddressLine1($item->shipping_line1);
                    $address->setAddressLine2($item->shipping_line2);
                    $address->setCity($item->shipping_city);
                    $address->setStateProvinceCode($item->shipping_state);
                    $address->setPostalCode($item->shipping_zip);
                    $address->setCountryCode('US');
                    break;
                }
            }
        }

        $mrtk = new Satori();
        $mrtk->create(0);
        try {
            //Just return a success result if service is down
            $mrtk->connect(config('app.host_config.mailRoomToolKit'), 5150, 5);
        } catch (Exception $e) {
            //Just return a success result if service is down
            (new Log())->logError('Core', 'Could not connect to MRTK');
            return ['status' => 'success'];
        }

        $certifyResponse = $mrtk->certifyAddress($address);
        if (intval($certifyResponse['status']) > 100 || in_array($certifyResponse['status'], array(92, 93))) {
            $validationResult['status'] = 'error';
        } else {
            //Just return a success result if service is down
            $validation = $ups->validateAddress($certifyResponse['address']);
            if (is_array($validation) && array_key_exists('connection_error', $validation)) { //ups is downs
                return ['status' => 'success'];
            }
            $validationResult = array();
            if (!$validation->isValid()) {
                if ($validation->noCandidates() || $validation->isAmbiguous()) {
                    $validationResult['status'] = 'error';
                } else {
                    $candidateAddress = $validation->getCandidateAddressList()[0];
                    $validationResult['status'] = 'warning';
                    $validationResult['suggestion'] = array(
                        'line1' => $candidateAddress->getAddressLine(1),
                        'line2' => $candidateAddress->getAddressLine(2),
                        'city'  => $candidateAddress->getCity(),
                        'state' => $candidateAddress->getStateProvince(),
                        'zip'   => $candidateAddress->getPostalCode()
                    );
                }
            } else {
                //address is valid but lets see if they made any significant changes
                $validAddress = $validation->getValidatedAddress();
                $numberChange = levenshtein(
                    preg_replace(
                        "/[^0-9]/",
                        "",
                        $validAddress->getAddressLine(1) . ' ' . $validAddress->getAddressLine(2)
                    ),
                    preg_replace(
                        "/[^0-9]/",
                        "",
                        $address->getAddressLine1() . ' ' . $address->getAddressLine2()
                    )
                );
                $lineChange = levenshtein(
                    $validAddress->getAddressLine(1) . ' ' . $validAddress->getAddressLine(2),
                    $address->getAddressLine1() . ' ' . $address->getAddressLine2()
                );
                $fullChange = levenshtein(
                    $validAddress->getAddressLine(1) . ' ' . $validAddress->getAddressLine(2) . ' ' .
                    $validAddress->getCity() . ' ' . $validAddress->getStateProvince() . ' ' .
                    $validAddress->getPostalCode(),
                    $address->getAddressLine1() . ' ' . $address->getAddressLine2() . ' ' . $address->getCity(
                    )
                    . ' ' . $address->getStateProvinceCode() . ' ' . $address->getPostalCode()
                );

                if ($fullChange > 25 || $lineChange > 15 || $numberChange > 0) {
                    $validationResult['status'] = 'warning';
                    $validationResult['suggestion'] = array(
                        'line1' => $validAddress->getAddressLine(1),
                        'line2' => $validAddress->getAddressLine(2),
                        'city'  => $validAddress->getCity(),
                        'state' => $validAddress->getStateProvince(),
                        'zip'   => $validAddress->getPostalCode()
                    );
                } else {
                    $validationResult['status'] = 'success';
                }
            }
        }
        return $validationResult;
    }

    public function updatePaymentAmount()
    {
        // get total
        $shipmentTotal = $this->getTotal('excludeCanceled');
        $paymentBalance = $shipmentTotal;

        // get payments
        $lastPayment = null;
        foreach ($this->payments() as $payment) {
            if ($payment->status == 'captured') {
                $paymentBalance -= $payment->amount;
                continue;
            }
            switch ($payment->calc_type) {
                case 'percentage':
                    $payment->amount = $payment->calc_value * $shipmentTotal;
                    break;

                case 'dollar':
                    $payment->amount = ($payment->calc_value < $paymentBalance) ? $payment->calc_value :
                        $paymentBalance;
                    break;
            }
            $payment->save();
            $paymentBalance -= $payment->amount;
            $lastPayment = $payment;
        }

        // last payment will account for any difference
        if ($lastPayment && $paymentBalance != 0) {
            $lastPayment->amount += $paymentBalance;
            $lastPayment->save();
        }
    }

    public function getTotal($exclude = null)
    {
        $total = 0;
        foreach ($this->items() as $item) {
            if ($exclude == 'excludeCanceled' && $item->status == 'canceled') {
                continue;
            } else {
                $total += floatval($item->total);
            }
        }
        return $total;
    }

}
