<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 9/18/17
 * Time: 3:46 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\ImportListing\Image;
use App\Core\Models\OrderCore\User\MlsOffice;

class ImportListing extends BaseModel
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
    protected $table = 'import_listing';

    protected $guarded = [];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(Image::class, 'listing_id', 'id');
    }

    /**
     * @return MlsOffice
     */
    public function getUserMlsOffice()
    {
        $importListing = $this;
        $userMlsOffice = (new MlsOffice())
            ->join('mls_office as mo', function ($join) use (&$importListing) {
                $join->on('mo.id', '=', 'user_mls_office.mls_office_id')
                    ->where('mo.public_id', '=', $importListing->agent_office_id);
            })
            ->join('user_pulse_settings as ups', function ($join) use (&$importListing) {
                $join->on('ups.user_id', '=', 'user_mls_office.user_id')
                    ->where('ups.mls_agent_public_id', '=', $importListing->agent_public_id);
            })
            ->first();
        if ($userMlsOffice) {
            return $userMlsOffice;
        } else {
            return new MlsOffice();
        }
    }
}