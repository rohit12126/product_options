<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 8/10/16
 * Time: 4:53 PM
 */

namespace App\Core\Models\OrderCore\User;


use App\Core\Models\BaseModel;

class AuthToken extends BaseModel
{
    /**
     * Override default
     */
    protected $primaryKey = 'user_id';

    /**
     * Override default
     *
     * @var bool
     */
    public $incrementing = false;

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
    protected $table = 'user_auth_token';
}