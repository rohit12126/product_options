<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/19/16
 * Time: 3:10 PM
 */

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;

class Job extends BaseModel
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
    protected $table = 'jobs';

}
