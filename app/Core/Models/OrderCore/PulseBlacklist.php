<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 4/18/17
 * Time: 10:22 AM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;

class PulseBlacklist extends BaseModel
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
    protected $table = 'pulse_blacklist';

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
        return $this->hasOne('App\Core\Models\OrderCore\PulseOrderType', 'id', 'black_list_order_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id', 'id');
    }
}