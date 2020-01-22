<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

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
    protected $table = 'address';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $_rules = [
        'line1' => 'required|regex:/(?=.*[0-9])(?=.*[\s])(?=.*[a-zA-Z]).{4,}/',
        'city' => 'required|alpha_spaces',
        'state' => 'required|alpha|min:2',
        'zip' => 'required|numeric|min:5'
    ];

    /**
     * Establish a relationship to the Account model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Limit results to EmailExpress addresses
     * @param $query
     * @return mixed
     */
    public function scopeEmx($query)
    {
        return $query->where('label', '=', 'EmailExpress');
    }
}