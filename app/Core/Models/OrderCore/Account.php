<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Account\Data as AccountData;
use Carbon\Carbon;
use App\Core\Models\OrderCore\User;

class Account extends BaseModel
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
    protected $table = 'account';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bankcards()
    {
        return $this->hasMany('App\Core\Models\OrderCore\Bankcard', 'account_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function getDefaultBankcard()
    {
        return $this->bankcards()->where('is_primary', '=', 1)->first();
    }

    /**
     * Get the parent account of an account.
     *
     * @return mixed
     */
    public function parentAccount()
    {
        return $this->where('id', '=', $this->parent_account_id)->first();
    }

    /**
     * Get the parent account of an account.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentAccountWithSite()
    {
        return $this->belongsTo(static::class, 'parent_account_id')->with('site');
    }

    /**
     * Establish the relationship to the address model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'account_id', 'id');
    }

    /**
     * Establish the relationship to the address model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phones()
    {
        return $this->hasMany(Phone::class, 'account_id', 'id');
    }

    /**
     * @return Site
     */
    public function getSiteWithFailOver()
    {
        if (is_null($site = $this->site)) {
            $site = Site::find(config('app.server_config.defaultSiteId'));
        }
        return $site;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function site()
    {
        return $this->hasOne(Site::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function data()
    {
        return $this->belongsTo(AccountData::class, 'id', 'account_id');
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getDataValue($name)
    {
        $data = $this->data()->where('name', $name)->first();
        return (null !== $data ? $data->value : null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activePromotions()
    {
        return $this->belongsToMany(Promotion::class, 'account_promotion', 'account_id', 'promotion_id')
            ->where('date_start', '<=', Carbon::now())
            ->where('date_end', '>=', Carbon::now())
            ->orderBy('date_start');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activeAutoPromos()
    {
        return $this->activePromotions()
            ->join('promotion_rules_promotion as prp', 'prp.promotion_id', '=', 'promotion.id')
            ->join('promotion_rule as pr', function ($join) {
                $join->on('pr.id', '=', 'prp.promotion_rule_id')
                    ->where('pr.name', '=', 'autoApply')
                    ->where('pr.value', '=', '1');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'account_promotion', 'account_id', 'promotion_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function salesReps()
    {
        return $this->belongsToMany(SalesRep::class, 'account_sales_rep', 'account_id', 'sales_rep_id');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'account_id', 'id');
    }
}