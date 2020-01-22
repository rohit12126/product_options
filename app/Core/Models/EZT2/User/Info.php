<?php

namespace App\Core\Models\EZT2\User;
use App\Core\Models\BaseModel;

class Info extends BaseModel
{
    protected $connection = 'ezt2';

    protected $primaryKey = 'ezt_user_id';

    public $timestamps = false;

    protected $table = 'user_info';

    protected $guarded = [];

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
}