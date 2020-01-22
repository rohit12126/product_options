<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/20/16
 * Time: 10:54 AM
 */

namespace App\Core\Models\OrderCore\Mls;
use \App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Listing;
use App\Core\Models\OrderCore\Mls;

class Provider extends BaseModel
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
    protected $table = 'mls_provider';

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mapping()
    {
        return $this->hasMany(Mapping::class, 'mls_provider_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mls()
    {
        return $this->hasMany(Mls::class, 'mls_provider_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listings()
    {
        return $this->hasMany(Listing::class, 'mls_provider_id', 'id');
    }
}