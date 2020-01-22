<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/16/16
 * Time: 2:44 PM
 */

namespace App\Core\Models\EZT2;

use App\Core\Models\BaseModel;

class ImageTypes extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    /**route
     * Specify the table to use.
     *
     * @var string
     */

    protected $table = 'image_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that are required.
     *
     * @var array
     */
    protected $_rules = [

    ];

    /**
     * Specify the database connection to be used for the query.
     *
     * @param $connection
     */
    public function changeConnection($connection)
    {
        $this->connection = $connection;
    }

}