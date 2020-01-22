<?php

namespace App\Core\Models\OrderCore\User;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class GA extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = 'user_id';

    protected $table = 'user_ga';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'source',
        'medium',
        'term', 
        'content', 
        'campaign'
    ];

    public $timestamps = false;

    public $incrementing = false;

}