<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Mls\Office;

class Broker extends BaseModel
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
    protected $table = 'broker';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return mixed
     */
    public static function getBrokers()
    {
        $brokers = self::pluck('name', 'id');
        $brokers->prepend('Broker', '');
        return $brokers;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offices()
    {
        return $this->hasMany(Office::class, 'broker_id', 'id');
    }
}