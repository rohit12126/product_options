<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/13/16
 * Time: 2:36 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Invoice\Data;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Models\OrderCore\Invoice\Promotion as InvoicePromotion;
use App\Core\Models\OrderCore\Invoice\Shipment;
use App\Core\Exceptions\PromotionException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Invoice extends BaseModel
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
    protected $table = 'invoice';

    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'invoice_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'invoice_id', 'id');
    }

    /**
     * Establish a relationship to the user.
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @param null $name
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getData($name = null)
    {
        return $this->hasMany(Data::class, 'invoice_id', 'id')->where('name', $name)->first();
    }

    /**
     * @param $name
     * @param $value
     */
    public function setDataValue($name, $value)
    {
        if (is_null($data = $this->getData($name))) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->invoice_id = $this->id;
        $data->save();
    }

    /**
     * @param null $code
     */
    public function setPromotion($code = null)
    {
        $dateSubmitted = $this->items()
            ->where('product_id', '>', '0')
            ->orderBy('date_submitted')
            ->first()
            ->date_submitted;
        $dateSubmitted = is_null($dateSubmitted) ? date('Y-m-d H:i:s', time()) : date('Y-m-d H:i:s', $dateSubmitted);

        $promotion = Promotion::where('code', $code)
            ->whereRaw("? BETWEEN date_start AND date_end", [$dateSubmitted])
            ->first();

        if ($promotion) {
            if (!$promotion->isEligible($this)) {
                throw new PromotionException($promotion->errors[0]);
            } else {
                $this->addPromotion($promotion);
                $this->promotion_id = $promotion->id;
                $this->save();

                foreach ($this->items as $item) {
                    $item->setPromotion($promotion);
                }
            }

        } else {
            throw new PromotionException('Promotion code entered is not a valid promotion');
        }
    }

    /**
     * @return bool|mixed
     */
    public function getBaseSiteId()
    {
        if($baseSiteId = $this->site->parent_site_id > 0) {
            return $baseSiteId;
        } else {
            return $this->site_id;
        }
    }

    /**
     * @param Promotion $promotion
     */
    public function addPromotion(Promotion $promotion)
    {
        $insertCheck = InvoicePromotion::where('invoice_id',$this->id)->where('promotion_id',
                                                                              $promotion->id)->first();
        if (!$insertCheck) {
            InvoicePromotion::create(
                [
                    'invoice_id'   => $this->id,
                    'promotion_id' => $promotion->id
                ]
            );
        }
    }

    /**
     * Return the last used promotion for the invoice.
     *
     * @return mixed
     */
    public function getPromotionCode()
    {
        $invoicePromo = InvoicePromotion::where('invoice_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();
        return (null !== $invoicePromo ? $invoicePromo->promotion->code : null);
    }

    /**
     * @return mixed
     */
    public function promoTotal()
    {
        $this->load('items');
        return $this->items()->sum('promotion_amount');
    }

    /**
     * @return mixed
     */
    public function total()
    {
        $this->load('items');
        return $this->items()->sum('total');
    }

    /**
     * @param int $markupPercentage
     * @return \Illuminate\Support\Collection
     */
    public function getShippingOptions($markupPercentage = 0)
    {
        $shippingOptions = ShippingOption::all();

        $availableOptions = array();
        foreach ($shippingOptions as $shippingOption) {
            $availableOptions[$shippingOption->service_code] = $shippingOption->toArray();
            $availableOptions[$shippingOption->service_code]['eta'] = '';
            $availableOptions[$shippingOption->service_code]['total'] = 0;
        }

        $shipments = $this->shipments;
        foreach ($shipments as $shipment) {
            $quotes = $shipment->getShippingQuotes(array(), $markupPercentage);
            $foundMethods = array_intersect_key($availableOptions, $quotes);
            foreach ($foundMethods as $foundMethod) {
                if (array_key_exists($foundMethod['service_code'], $availableOptions)) {
                    if (count($shipments) == 1 && array_key_exists('eta',
                                                                   $quotes[$foundMethod['service_code']])) {
                        $availableOptions[$foundMethod['service_code']]['eta'] = $quotes[$foundMethod['service_code']]['eta'];
                    } else {
                        $availableOptions[$foundMethod['service_code']]['eta'] = 'Delivery Estimate Unavailable from UPS';
                    }
                    //remove options without quote
                    if (!isset($quotes[$foundMethod['service_code']]['total'])){
                        unset($availableOptions[$foundMethod['service_code']]);
                    } else {
                        $availableOptions[$foundMethod['service_code']]['total'] += $quotes[$foundMethod['service_code']]['total'];
                    }
                }
            }
        }
        return collect($availableOptions);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return array
     */
    public function submitOrder()
    {
        // mark discount as used
        if ($this->discount_id) {
            $this->discount->is_active = 0;
            $this->discount->save();
        }
        $dateSubmitted = Carbon::now();
        $this->load('shipments');//refresh shipments
        $isReadyForProduction = false;
        $scheduledDates = array();
        foreach ($this->shipments as $invoiceShipment) {
            if (in_array($invoiceShipment->status, array('incomplete', 'scheduled'))) {
                if (
                    $invoiceShipment->date_scheduled &&
                    (
                        Carbon::createFromFormat('Y-m-d h:i:s', $invoiceShipment->date_scheduled)->gt($dateSubmitted)
                    )
                ) {
                    $newStatus = 'scheduled';
                    $scheduledDates[] = $invoiceShipment->date_scheduled;
                } else {
                    $newStatus = 'ready for production';
                }
                $hasItems = false;
                foreach ($invoiceShipment->items as $invoiceItem) {
                    if (!$invoiceItem->date_submitted) {
                        $invoiceItem->date_submitted = $dateSubmitted;
                    }
                    $invoiceItem->status = $newStatus;
                    $invoiceItem->save();
                    $hasItems = true;
                }
                if ($hasItems) {
                    $invoiceShipment->status = $newStatus;
                    $invoiceShipment->save();
                    if ($newStatus == 'ready for production') {
                        $isReadyForProduction = true;
                    }
                }
            }
        }
        if (in_array($this->status, array('incomplete', 'scheduled', 'waiting for auth'))) {
            if ($isReadyForProduction) {
                $this->status = 'ready for production';
            } else {
                $this->status = 'scheduled';
            }
            $this->is_active = 1;
            $this->save();
        }
        return $scheduledDates;
    }


    /**
     * Used for situations where an order was started by a user, then they logged in/registered.
     *
     * @param $newUserId
     */
    public function updateInvoiceUser($newUserId)
    {
        $this->items()->get()->each(function ($item, $key) use ($newUserId) {
            $item->addressFiles()->get()->each(function ($addressFile, $addressFileKey) use ($newUserId) {
                $addressFile->update(['user_id' => $newUserId]);
            });
            $item->designFiles()->get()->each(function ($designFile, $designFileKey) use
            ($newUserId) {
                $designFile->update(['user_id' => $newUserId]);
            });
        });

        $newUser = User::find($newUserId);
        $this->update([
            'user_id' => $newUserId,
            'contact_name' => $newUser->contact_name,
            'contact_email' => $newUser->email,
            'contact_phone' => $newUser->phone,
            'contact_method' => $newUser->preferred_contact_method
        ]);

        // redetermine eligibility
        if ($this->promotion && !$this->promotion->isEligible($this)) {
            // unset promotion
            $this->unsetPromotion();
        }
        // multi-pay discount code is now ineligible
        if ($this->discount) {
            $this->unsetDiscount();
        }
    }

    /**
     * Remove promotion(s) from an invoice.
     *
     * @return mixed
     * @throws \Exception
     */
    public function removePromotions()
    {
        return InvoicePromotion::where('invoice_id', $this->id)->delete();
    }

    /**
     * Get the max discount for an invoice, based on it's items.
     *
     * @param $maxPieces
     * @param $promotion null|Promotion $includePromo - A promo code to be included in discount calculations.
     * @return int
     */
    public function getMaxDiscount($maxPieces, $promotion = null)
    {
        $maxDiscount = 0;
        $this->load('items')->items()->each(function ($item, $key) use(&$maxDiscount, $maxPieces, $promotion) {
            if (!is_null($item->product_id)) {
                if ($promotion) {

                    $promoResult = DB::select(
                        'SELECT order_core.fn_invoice_item_promo_calc(?,?,?) as promotion_amount',
                        [
                            $item->id,
                            $promotion->id,
                            NULL
                        ]
                    );
                    $item->refresh();
                    $maxDiscount += (($item->unit_price * $item->quantity) - $promoResult[0]->promotion_amount);
                } else {
                    $maxDiscount += ($item->unit_price * $maxPieces);
                }
            }
        });

        return $maxDiscount;
    }

    /**
     * @param $discount
     * @return $this
     */
    public function setDiscount($discount)
    {
        if (null !== $discount) {
            $this->update([
                'discount_id' => $discount->id
            ]);
            $discount->calculate($this);
        }

        $this->refresh();
        return $this;
    }

    /**
     * Scope submitted invoices.
     *
     * @param $query
     * @return mixed
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotIn('status', ['incomplete', 'canceled']);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function unsetPromotion()
    {
        $this->removePromotions();
        $this->promotion()->dissociate();
        $this->save();
        foreach ($this->items as $item) {
            $item->promotion()->dissociate();
            $item->promotion_amount = 0;
            $item->save();
        }
        if (isset($this->relations['items'])) {
            // preemptive unset dirty items
            unset($this->relations['items']);
        }
    }

    public function unsetDiscount()
    {
        $this->discount()->dissociate();
        $this->save();
        foreach ($this->items as $item) {
            $item->discount()->dissociate();
            $item->discount_amount = 0;
            $item->save();
        }
        if (isset($this->relations['items'])) {
            // preemptive unset dirty items
            unset($this->relations['items']);
        }
    }
}