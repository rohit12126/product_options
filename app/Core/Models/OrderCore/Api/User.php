<?php

namespace App\Core\Models\OrderCore\Api;

//use Carbon\Carbon;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\User as MainUser;

class User extends BaseModel
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
    protected $table = 'api_user';

    protected $guarded = ['date_last_usage'];
    public $timestamps =false;
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userDetail()
    {
        return $this->hasOne(MainUser::class, 'id', 'user_id');
    }    
}