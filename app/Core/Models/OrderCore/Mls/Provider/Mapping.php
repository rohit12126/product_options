<?php

namespace App\Core\Models\OrderCore\Mls;
use App\Core\Models\BaseModel;

/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 9/27/17
 * Time: 2:44 PM
 */

class Mapping extends BaseModel
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
    protected $table = 'mls_provider_mapping';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function listing()
    {
        return $this->belongsTo(Provider::class, 'mls_provider_id', 'id');
    }
}