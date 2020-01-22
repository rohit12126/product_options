<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/25/16
 * Time: 10:48 AM
 */

namespace App\Core\Models\OrderCore\Listing;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\City;
use App\Core\Models\OrderCore\Discount;
use App\Core\Models\OrderCore\State;
use App\Core\Models\OrderCore\User;
use App\Core\Models\OrderCore\Zipcode;

class Address extends BaseModel
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
    protected $table = 'listing_address';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'line1',
        'line2',
        'city_id',
        'state_id',
        'zipcode_id'
    ];

    protected $sanitize = [
        'line1' => 'trim|preg_replace:/[\\s]{2,}/: :{{ VALUE }}',
        'line2' => 'trim|preg_replace:/[\\s]{2,}/: :{{ VALUE }}'
    ];

    protected $validationRules = [
        'line1'         => 'required',
        'city'          => 'required|alpha_spaces',
        'state_id'      => 'required',
        'zipcode'       => 'required|digits:5|validZip',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zipcode()
    {
        return $this->belongsTo(Zipcode::class, 'zipcode_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function discount()
    {
        return $this->belongsToMany(Discount::class, 'listing_address_discount', 'listing_address_id');
    }

    /**
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        return parent::fill(self::translateToIds($attributes));
    }

    /**
     * Search for, or create a listing address.
     * Handles translating address attribute strings to IDs.
     *
     * @param $addressData
     * @return Address
     */
    public static function fetchOrCreate($addressData)
    {
        return self::firstOrCreate(
            self::translateToIds($addressData)
        );
    }

    /**
     * Fill and translate string literals of city, state, zipcode to actual id's for those domain tables. Useful when
     * needing to create or update address (e.g. 'city' => 'Portland', might translate that to $this->city_id = 1).
     *
     * @param array $attributes
     * @return array - converted $attributes
     */
    protected static function translateToIds(array $attributes)
    {
        foreach ($attributes as $attrKey => $attrValue) {
            if ('city' == $attrKey && $attrValue) {
                $attributes['city_id'] = City::firstOrCreate(['name' => $attrValue])->id;
                unset($attributes['city']);
            } else if ('state' == $attrKey && $attrValue) {
                $attributes['state_id'] = State::where('abbrev', $attrValue)->firstOrFail()->id;
                unset($attributes['state']);
            } else if ('zipcode' == $attrKey && $attrValue) {
                $attributes['zipcode_id'] = Zipcode::firstOrCreate(['zip_code' => $attrValue])->id;
                unset($attributes['zipcode']);
            } else if ('zip' == $attrKey && $attrValue) {
                $attributes['zipcode_id'] = Zipcode::firstOrCreate(['zip_code' => $attrValue])->id;
                unset($attributes['zip']);
            }

        }

        return $attributes;
    }
}
