<?php

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;
use Carbon\Carbon;

class Log extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Turn off timestamps
     */
    public $timestamps = false;

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'error_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'is_active',
        'source',
        'date_created',
        'message'
    ];

    /**
     * Log errors to the database.
     * Return the hex representation of the id.
     * The id is obscured as hex to prevent users from seeing the ID, which may cause users to lose confidence in site.
     * The hex is returned so that cust svc can relay error codes to dev as needed.
     *
     * TODO: Improve logging/FS integration to negate need to show error IDs on website.
     *
     * @param $source
     * @param $error
     * @return string
     */
    public function logError($source, $error)
    {
        $this->setRawAttributes([
            'is_active'     => 1,
            'date_created'  => Carbon::now(),
            'source'        => $source,
            'message'       => serialize([
                'message'       => $error,
                'requestUrl'    => request()->fullUrl(),
                'userAgent'     => request()->header('User-Agent'),
                'userIp'        => request()->ip(),
                'requestMethod' => request()->method()
            ])
        ]);
        $this->save();

        //Output the row ID as hex to send to userland.
        return dechex($this->id);
    }
}
