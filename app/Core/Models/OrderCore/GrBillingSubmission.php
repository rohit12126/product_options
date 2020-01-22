<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/16/18
 * Time: 1:16 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;

class GrBillingSubmission extends BaseModel
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
    protected $table = 'gr_billing_submission';

    protected $guarded = [];

}