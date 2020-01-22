<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 12/27/17
 * Time: 10:31 AM
 */

namespace App\Core\Models\EZT2\User\Image;

use App\Core\Models\BaseModel;

class Type extends BaseModel
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
    protected $primaryKey = 'im_type_id';
    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'image_types';

    public $timestamps = false;
}