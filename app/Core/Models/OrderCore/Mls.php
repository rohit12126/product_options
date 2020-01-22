<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Mls\Office;
use App\Core\Models\OrderCore\Mls\Provider;

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
    protected $table = 'mls';

    /**
     * Fields to append to the model
     * 
     * @var array
     */
    protected $appends = ['full_name'];

    /**
     * Relations to eager load
     * 
     * @var array
     */
    protected $with = ['state'];

    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * Merge the public ID and MLS name
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->public_id . ' - ' . $this->name . ' - ' . $this->state->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function state()
    {
        return $this->hasOne(State::class, 'id', 'state_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'mls_provider_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function offices()
    {
        return $this->belongsToMany(Office::class, 'mls_office_mls', 'mls_id', 'mls_office_id')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\Illuminate\Database\Query\Builder
     */
    public function officeCountRelation()
    {
        return $this->hasOne(Office\Mls::class)->selectRaw('mls_id, count(*) as count')->groupBy('mls_id');
    }

    /**
     * @return bool
     */
    public function offersAutomation()
    {
        return ('available' == $this->status);
    }

    /**
     * @return Mls[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getMlsList()
    {
        //The self::pluck() method won't work here since we need to concat params to create the display name
        $mls = self::all();
        $mls->map(function ($m, $k) {
            return $m->full_name = $m->public_id . ' - ' . $m->name . ' - ' . $m->state->name;
        });

        return $mls;
    }
}
