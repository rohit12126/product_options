<?php

namespace App\Core\Models\OrderCore\EmailExpress;

use App\Core\Models\OrderCore\User;
use Illuminate\Database\Eloquent\Model;

class EmailExpressClient extends Model
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'cakemail_client_id';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'email_express_client';

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Define the relationship to the Order Core user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderCoreUser()
    {
        return $this->belongsTo(User::class, 'order_core_user_id', 'id');
    }

    /**
     * Define inverse relationship to CakeMail plans.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailExpressPlans()
    {
        return $this->hasMany(
            EmailExpressPlan::class,
            'cakemail_client_id',
            'cakemail_client_id'
        );
    }

    /**
     * Get the client's active CakeMail plan.
     *
     * @return mixed
     */
    public function activePlan()
    {
        return $this->emailExpressPlans()->active()->first();
    }
}
