<?php
/**
 * Created by Apoorv Vyas.
 * 
 * Date: 10/18/16
 * Time: 10:35 AM
 */

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class Tier extends BaseModel
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
    protected $table = 'promotion_tier';


    public $errors = [];

    
}