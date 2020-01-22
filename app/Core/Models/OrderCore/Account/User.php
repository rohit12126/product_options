<?php

namespace App\Core\Models\OrderCore\Account;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Account;

class User extends BaseModel
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
    protected $table = 'account_user';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that are default.
     *
     * @var array
     */
    protected $attributes = [
       'role' => 'user'
    ];

    /**
     * Establish a relationship to the user's account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);        
    }
}