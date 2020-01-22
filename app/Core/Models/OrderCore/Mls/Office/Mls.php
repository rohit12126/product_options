<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 10/5/17
 * Time: 11:21 AM
 */

namespace App\Core\Models\OrderCore\Mls\Office;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Mls as ParentMls;
use App\Core\Models\OrderCore\Mls\Office;

class Mls extends BaseModel
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
    protected $table = 'mls_office_mls';

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
    public function mls()
    {
        return $this->hasMany(ParentMls::class, 'id', 'mls_id');
    }
}