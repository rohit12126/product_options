<?php

/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/9/16
 * Time: 10:46 AM
 */

namespace App\Core\Models\PhpSession;
use App\Core\Models\BaseModel;

class LaravelSession extends BaseModel
{

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'sessions';

    /**route
     * Specify the table to use.
     *
     * @var string
     */

    protected $table = 'laravel_session';

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

    
    private $_parsedData = [];
    
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