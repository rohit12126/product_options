<?php
/**
 * order_core.api_data is a table for storing non-normalized data for api user.
 */

namespace App\Core\Models\OrderCore\Api;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Api\User;

class Data extends BaseModel
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
    protected $table = 'api_data';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'api_user_id', 'id');
    }

    /**
     * Set the serialize value for data column.
     *
     * @param  string  $value
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = ('' == $value || is_null($value)) ? '' : serialize($value);
    }

    /**
     * Get the unserialize data column value.
     *
     * @param  string  $value
     * @return string
     */
    public function getDataAttribute($value)
    {
        return unserialize($value);
    }
   
}