<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 9/18/17
 * Time: 3:46 PM
 */

namespace App\Core\Models\OrderCore\ImportListing;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\ImportListing;

class Image extends BaseModel
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
    protected $table = 'import_listing_image';

    protected $guarded = [];

    /**
     * Establish a relationship to the listing.
     * This is used for the PulseMailing application.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function listing()
    {
        return $this->belongsTo(ImportListing::class, 'listing_id', 'id');
    }

}