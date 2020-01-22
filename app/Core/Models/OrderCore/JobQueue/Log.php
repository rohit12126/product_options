<?php
namespace App\Core\Models\OrderCore\JobQueue;

use App\Core\Models\BaseModel;

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

    protected $table = 'job_queue_log';

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