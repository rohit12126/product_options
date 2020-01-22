<?php
/**
 * order_core.user_referral is a table for storing non-normalized data for a user.
 */

namespace App\Core\Models\OrderCore\User;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Referral extends BaseModel
{
    use HasCompositePrimaryKey;

    protected $connection = 'order_core';

    protected $primaryKey = ['referrer_user_id', 'referee_user_id'];

    protected $table = 'user_referral';

    public $timestamps = false;

    public $incrementing = false;

}