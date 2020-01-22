<?php

namespace App\Core\Models\EZT2\User;
use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\User\Image\Type;

class Image extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';


    /**
     * Specify the primary key to use.
     *
     * @var string
     */
    protected $primaryKey = 'image_id';
    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'user_images';

    public $timestamps = false;

    /**
     * Define the relationship to image_types.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(Type::class, 'im_type_id', 'image_type');
    }

    /**
     * Get the company logos
     *
     * @return mixed
     */
    public function companyLogos()
    {
        $queryParams = [
            'library_flag' => 1,
            'image_type' => 3,
            'ezt_user_id' => 0
        ];
        
        return $this->where($queryParams)->orderBy('date_added', 'DESC');;
    }

    /**
     * Get the industry logos
     *
     * @return mixed
     */
    public function industryLogos()
    {
        $queryParams = [
            'library_flag' => 1,
            'image_type' => 4,
            'ezt_user_id' => 0
        ];

        return $this->where($queryParams)->orderBy('date_added', 'DESC');;
    }
}