<?php

/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/25/17
 * Time: 9:37 AM
 */

namespace App\Core\Models\Excopy\Job;

use App\Core\Models\BaseModel;

class Invoice extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'excopy';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'job_invoice';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Turn off timestamps
     */
    public $timestamps = false;

    protected $primaryKey = 'invoice_num';
}