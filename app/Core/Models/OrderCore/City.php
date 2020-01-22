<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/25/16
 * Time: 10:53 AM
 */

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class City extends BaseModel
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
    protected $table = 'city';

    protected $guarded = [

    ];

    public $timestamps = false;

    protected $appends = ['displayName'];


    /**
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return ucwords(strtolower($this->name));
    }
}