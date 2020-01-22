<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/16/16
 * Time: 3:58 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\JobQueue\Log as JobQueueLog;

class JobQueue extends BaseModel
{
    const UPDATED_AT = null; // work-around

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

    protected $table = 'job_queue';

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

    /**
     * Get the result of a job that was sent to the queue.
     *
     * @param int $timeout
     */
    public function getJobResult($timeout = 120)
    {
        $startTime = time();

        // pull until done or error
        while (!in_array($this->status, array('done', 'error'))) {
            if (time() > $startTime + $timeout) {
                $this->status = 'error';
                $this->result = serialize(
                    array("100" => "There's an issue with your file. Call 800.260.5887 for help.")
                );
                $this->save();
                $jobQueueLog = new JobQueueLog();
                $jobQueueLog->job_queue_id = $this->id;
                $jobQueueLog->command = 'getReturnValue()';
                $jobQueueLog->message = 'Timed out while waiting for getReturnValue()';
                $jobQueueLog->save();
                break;
            }
            usleep(500000);
            $this->reload();
        }
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return unserialize($this->result);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return unserialize($this->data);
    }


    /**
     *
     */
    public function reload()
    {
        $instance = new static;

        $instance = $instance->newQuery()->find($this->{$this->primaryKey});

        $this->attributes = $instance->attributes;

        $this->original = $instance->original;
    }

    /**
     * @param mixed $value
     * @return BaseModel|void
     */
    public function setUpdatedAt($value)
    {
        ;
    }
    
}