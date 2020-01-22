<?php

namespace App\Core\Models\EZT2\User\Info;
use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\States;
use App\Core\Models\OrderCore\User\PulsePhoneXref;
use App\Core\Models\EZT2\User\Image;
use App\Core\Models\EZT2\Phone;
use App\Core\Models\EZT2\PhoneType;

class Prefs extends BaseModel
{
    protected $connection = 'ezt2';

    protected $table = 'user_info_prefs';

    protected $guarded = [];

    public $timestamps = true;

    /**
     * The attributes that should be mutated to Carbon dates.
     *
     * @var array
     */
    protected $dates = [
        'date_updated',
        'date_created'
    ];

    /**
     * Get the phone numbers that are associated with this ezt2 user
     *
     * @return mixed
     */
    public function phonesXref()
    {
        return $this->hasMany(PulsePhoneXref::class, 'user_id', 'ezt_user_id');
    }

    /**
     * Create/update phone numbers and type.
     *
     * @param $count - Number of phone fields in the request
     * @param $request
     * @return $this
     */
    public function setPhones($count, $request)
    {
        for ($number = 1; $number <= $count; $number++) {
            $phoneName = "phone{$number}";
            $typeName = "phone{$number}_type";
            if ('' !== $request[$phoneName] && '' !== $request[$typeName]) {
                if (in_array($request[$typeName], PhoneType::getValidTypes())) {
                    $lookup = $this->phonesXref()->slot($number)->first();
                    if ($lookup) {
                        $lookup->phone->update([
                            'phone_number' => $request[$phoneName],
                            'phone_type' => $request[$typeName]
                        ]);
                    } else {
                        //Create a new phone if it doesn't exist
                        $phone = Phone::create([
                            'phone_number' => $request[$phoneName],
                            'phone_type' => $request[$typeName],
                            'ezt_user_id' => $this->ezt_user_id,
                            'user_info_prefs_id' => $this->id
                        ]);
                        PulsePhoneXref::create([
                            'user_id' => $this->ezt_user_id,
                            'slot' => $number,
                            'phone_id' => $phone->phone_id
                        ]);
                    }
                }
            } else {
                $lookup = $this->phonesXref()->slot($number)->first();
                if ($lookup) {
                    $lookup->delete();
                }
            }
        }

        return $this;
    }

    /**
     * Get all of the headshots for the user
     *
     * @return mixed
     */
    public function headshots()
    {
        $compositeKey = [
            'ezt_user_id' => $this->ezt_user_id,
            'image_type' => 2
        ];

        return Image::where($compositeKey)->get();
    }

    /**
     * Get the currently selected headshot for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function headshot()
    {
        return $this->hasOne(Image::class,'image_id' ,'mugshot');
    }

    /**
     * Limit results to the excopyz app.
     *
     * @param $query
     * @return mixed
     */
    public function scopeExcopyz($query)
    {
        return $query->where('app', 'excopyz');
    }

    /**
     * Limit results to the Pulse app.
     *
     * @param $query
     * @return mixed
     */
    public function scopePulse($query)
    {
        return $query->where('app', 'pulse');
    }

    public function stateRecord()
    {
        return $this->belongsTo(States::class, 'state', 'state_id');
    }
}