<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\User;

class Phone extends BaseModel
{
    protected $connection = 'order_core';

    protected $table = 'phone';

    public $timestamps = true;

    protected $fillable = [
        'number'
    ];

    /**
     * Get around the silly "NOT NULL" constraint on the extension column.
     *
     * @param $value
     */
    public function setExtensionAttribute($value)
    {
        $this->attributes['extension'] = ('' == $value || is_null($value)) ? '' : $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'preferred_contact_phone_id');
    }
}