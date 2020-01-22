<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 7/1/16
 * Time: 3:35 PM
 */

namespace App\Core\Models\EZT2;

use App\Core\Models\BaseModel;

class Variable extends BaseModel
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
    protected $table = 'variable';

}