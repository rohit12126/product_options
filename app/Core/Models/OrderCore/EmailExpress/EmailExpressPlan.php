<?php

namespace App\Core\Models\OrderCore\EmailExpress;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmailExpressPlan extends Model
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
    protected $primaryKey = 'id';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'email_express_plan';

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_on'
    ];

    /**
     * Define the relationship to the CakeMail User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function emailExpressUser()
    {
        return $this->belongsTo(
            EmailExpressClient::class,
            'cakemail_client_id',
            'cakemail_client_id'
        );
    }

    /**
     * Query scope for active plans.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Query scope for plans that expire today.
     *
     * @param $query
     * @return mixed
     */
    public function scopeExpired($query)
    {
        return $query->whereRaw('Date(expiration_date) <= CURDATE() AND date_suspended IS NULL');
    }
}
