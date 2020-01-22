<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/19/16
 * Time: 3:10 PM
 */

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Listing\Address as ListingAddress;
use App\Core\Models\OrderCore\Listing\Image;
use App\Core\Models\OrderCore\Listing\Status;
use App\Core\Models\OrderCore\Listing\Type;
use App\Core\Models\OrderCore\Mls\Provider;

class Listing extends BaseModel
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
    protected $table = 'listing';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mls_number',
        'bedrooms',
        'full_baths',
        'sqft',
        'listing_date',
        'price'
    ];

    protected $dates = [
        'date_created',
        'date_updated'
    ];

    protected $validationRules = [
        'price' => 'required|regex:/^[$\d,\.]+$/'
    ];


    /**
     * @return $this
     */
    public function validateOnImport()
    {
        $this->is_valid = false;

        if ($this->mainImage()->first()) {
            if ($this->validate($this->toArray(), [], $this->validationRules)) {
                $this->is_valid = true;
            }
        }

        $this->save();
        return $this;
    }

    /**
     * Define the Listing to User relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(Type::class, 'listing_type_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'listing_status_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function address()
    {
        return $this->belongsTo(ListingAddress::class, 'listing_address_id', 'id')
            ->with(['city', 'state', 'zipcode']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(Image::class)->with('image');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mainImage()
    {
        return $this->hasOne(Image::class)->where('main_image', 1)
            ->with('image');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mlsProvider()
    {
        return $this->belongsTo(Provider::class, 'id', 'mls_provider_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderHistory()
    {
        return $this->hasMany(PulseListingOrder::class)->with(['type', 'invoiceItem']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order()
    {
        return $this->hasOne(PulseListingOrder::class)->with('invoiceItem');
    }

    /**
     * @return mixed
     */
    public function submittedOrderHistory()
    {
        return $this->orderHistory()->submitted();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function blacklist()
    {
        return $this->hasMany(PulseBlacklist::class);
    }

    /**
     * @param $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @param $query
     */
    public function scopeNonblacklisted($query)
    {
        return $query->whereDoesntHave('blacklist');
    }

    /**
     * @param $excludedInvoiceItem
     * @param $requestedQuantity
     * @return |null
     */
    public function previousOrderAddressList($excludedInvoiceItem, $requestedQuantity)
    {
        $addressFile = null;
        //check if prior list exists for this listing
        $previousListingOrders = $this->orderHistory()->whereNotIn(
            'invoice_item_id',
            [$excludedInvoiceItem->id]
        )->get();
        if (count($previousListingOrders)) {
            foreach ($previousListingOrders as $previousListingOrder) {
                if (
                in_array(
                    $previousListingOrder->invoiceItem->status,
                    array('in production', 'in support', 'shipped')
                )
                ) {
                    $previousAddressFiles = $previousListingOrder->invoiceItem->addressFiles()->where('data_product_id', 2)->get();
                    foreach ($previousAddressFiles as $previousAddressFile) {
                        if (!is_null(
                                $previousAddressFile
                            ) && $previousAddressFile->count === $requestedQuantity
                        ) {
                            $addressFile = $previousAddressFile;
                            break;
                        }
                    }
                }
            }
        }
        return $addressFile;
    }
}
