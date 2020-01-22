<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/5/17
 * Time: 11:20 AM
 */

namespace App\Core\Models\OrderCore\User;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Mls\Office;
use App\Core\Models\OrderCore\User;

class MlsOffice extends BaseModel
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
    protected $table = 'user_mls_office';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offices()
    {
        return $this->hasMany(Office::class, 'id', 'mls_office_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}