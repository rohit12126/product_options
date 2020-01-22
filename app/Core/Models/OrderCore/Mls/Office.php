<?php

namespace App\Core\Models\OrderCore\Mls;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Broker;
use App\Core\Models\OrderCore\Mls;
use App\Core\Models\OrderCore\User;

class Office extends BaseModel
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
    protected $table = 'mls_office';

    protected $guarded = [
        'id',
        'date_created',
        'date_updated'
    ];

    protected $with = [
        'mls'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function broker()
    {
        return $this->hasOne(Broker::class, 'id', 'broker_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mls()
    {
        return $this->belongsToMany(Mls::class, 'mls_office_mls', 'mls_office_id', 'mls_id')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_mls_office', 'mls_office_id', 'user_id')->withTimestamps();
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1);
    }
}