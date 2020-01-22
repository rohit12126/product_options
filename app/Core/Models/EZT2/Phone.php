<?php

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;

class Phone extends BaseModel
{
    protected $connection = 'ezt2';

    protected $primaryKey = 'phone_id';

    protected $with = ['type'];

    protected $table = 'phone';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Establish a relationship with the phone type.
     * For example, cell, office, phone, etc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne('App\Core\Models\EZT2\PhoneType', 'phone_type', 'phone_type');
    }
}