<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 4/17/17
 * Time: 12:28 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\User\Project;
use App\Core\Models\OrderCore\Invoice\Item;

class PulseListingOrder extends BaseModel
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
    protected $table = 'pulse_listing_order';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(PulseOrderType::class, 'id', 'pulse_order_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceItem()
    {
        return $this->belongsTo(Item::class, 'invoice_item_id', 'id')->with('orderNumber');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeSubmitted($query)
    {
        return $query->whereHas('invoiceItem', function($q) {
            $q->whereNotNull('date_submitted');
            $q->join('invoice as i', 'i.id', '=', 'invoice_item.invoice_id');
            $q->orWhere('i.status', 'waiting for auth');
        });
    }

    /**
     * @param $qurey
     * @return mixed
     */
    public function scopeReview($qurey)
    {
        return $qurey->whereHas('invoiceItem', function($q) {
            $q->whereNotIn('invoice_item.status', ['canceled', 'shipped'])->join('invoice', 'invoice_item.invoice_id', '=', 'invoice.id')->where('invoice.is_active', '1');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userProject()
    {
        return $this->hasOne(Project::class, 'id', 'user_project_id');
    }

    /**
     * @return mixed
     */
    public static function getOrders()
    {
        //TODO - Grab all listing orders - done
        //TODO - Filter out cancelled, shipped invoice items - done
        //TODO - Include ii design files (front and back)
        //TODO - visual indicator of in prod vs incomp (ii.status) - done


        //TODO - Grab front and back from design files, add ui to toggle between 2
        //TODO - ii -> design_files

        return self::whereHas('invoiceItem', function ($q1) {
            $q1->whereNotIn('status', ['canceled', 'shipped']);
        })->whereHas('listing', function ($q2) {
            $q2->active();
        })->get();
    }
}