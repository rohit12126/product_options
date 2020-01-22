<?php

/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 7/1/16
 * Time: 9:58 AM
 */

namespace App\Core\Models\EZT2\Template;

use App\Core\Models\BaseModel;

class Instance extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    protected $primaryKey = 'id';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'template_instance';
    
}