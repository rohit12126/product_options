<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 4/10/17
 * Time: 3:59 PM
 */

namespace App\Core\Models\OrderCore\User;


use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\Phone;

class PulsePhoneXref extends BaseModel
{
    /**
     * Override default
     */
    protected $primaryKey = 'id';

    /**
     * Override default
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $guarded = [];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'user_pulse_phone_xref';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function phone()
    {
        return $this->hasOne(Phone::class, 'phone_id', 'phone_id');
    }

    /**
     * @param $query
     * @param $slotNumber
     * @return mixed
     */
    public function scopeSlot($query, $slotNumber)
    {
        return $query->where('slot', $slotNumber);
    }
}