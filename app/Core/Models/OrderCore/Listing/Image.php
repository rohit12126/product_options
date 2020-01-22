<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/20/16
 * Time: 10:35 AM
 */

namespace App\Core\Models\OrderCore\Listing;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Listing;
use App\Core\Traits\HasCompositePrimaryKey;
use App\Core\Models\EZT2\User\Image as UserImage;

class Image extends BaseModel
{
    use HasCompositePrimaryKey;
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $primaryKey = ['listing_id', 'image_id'];

    public $incrementing = false;

    protected $guarded = [];

    protected $table = 'listing_image';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function listing()
    {
        return $this->belongsTo(Listing::class, 'id', 'listing_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function image()
    {
        return $this->hasOne(UserImage::class, 'image_id', 'image_id');
    }
}
