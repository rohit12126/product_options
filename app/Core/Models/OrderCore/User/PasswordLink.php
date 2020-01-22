<?php

namespace App\Core\Models\OrderCore\User;

use App\Core\Models\BaseModel;

class PasswordLink extends BaseModel
{
    protected $connection = 'order_core';    

    protected $table = 'user_password_link';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'code',        
        'date_expire'
    ];

	/**     
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
    	return $this->belongsTo(User::class, 'id', 'user_id');
    }

}