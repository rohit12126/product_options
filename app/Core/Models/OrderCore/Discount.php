<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use Illuminate\Support\Facades\DB;
use App\Core\Models\OrderCore\Invoice\Item as InvoiceItem;
use App\Core\Models\OrderCore\Listing\Address as ListingAddress;

class Discount extends BaseModel
{
    const UPDATED_AT = null;

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
    protected $table = 'discount';

    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function listingAddress()
    {
        return $this->belongsToMany(ListingAddress::class, 'listing_address_discount', 'discount_id', 'listing_address_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @param \App\Core\Models\OrderCore\Invoice $invoice
     * @return mixed
     */
    public function calculate(Invoice $invoice)
    {
        DB::statement(
            'CALL sp_multipay_discount(?, ?)', [$this->id, $invoice->id]
        );

        return $this->refresh();
    }

    /**
     * Attempt to create a discount code for a listing.
     * Return null if eligibility check fails.
     *
     * @param $address - ListingAddress model
     * @param $user
     * @param $maxDiscount
     * @param $sourceAccount
     * @return self |null
     */
    public static function forAddress($address, $user, $maxDiscount, $sourceAccount)
    {
        $account  = $user->account();
        $usedDiscounts = DB::connection('order_core')
            ->table('listing_address_discount')
            ->join('discount', function ($join) use($address, $account) {
                $join->on('discount.id', '=', 'listing_address_discount.discount_id')
                ->where('discount.account_id', '=', $account->id)
                ->where('discount.is_active', '=', 0)
                ->where('listing_address_discount.listing_address_id', '=', $address->id);
            })
        ->get();

        if ($usedDiscounts->count()) {
            return null;
        }

        //todo: refactor for efficiency. for now, just need to get this out.
        $unusedDiscount = DB::connection('order_core')
            ->table('listing_address_discount')
            ->join('discount', function ($join) use($address,  $account) {
                $join->on('discount.id', '=', 'listing_address_discount.discount_id')
                    ->where('discount.account_id', '=', $account->id)
                    ->where('discount.is_active', '=', 1)
                    ->where('listing_address_discount.listing_address_id', '=', $address->id);
            })
            ->first();

        if ($unusedDiscount) {
            return Discount::find($unusedDiscount->discount_id);
        }

        $discount = self::create([
            'type' => 'percentage',
            'amount' => 100,
            'account_id' => $account->id,
            'source_account_id' => $sourceAccount,
            'max_discount' => $maxDiscount
        ]);

        $discount->listingAddress()->syncWithoutDetaching($address->id);

        return $discount;
    }

    /**
     * @param mixed $value
     * @return BaseModel|void
     */
    public function setUpdatedAt($value)
    {
        ;
    }


    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}