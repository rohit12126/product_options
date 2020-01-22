<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 7/1/16
 * Time: 9:43 AM
 */

namespace App\Core\Models\EZT2\Design\Customizable;

use App\Core\Models\BaseModel;

class Variable extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'customizable_design_variable';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variable()
    {
        return $this->hasOne('App\Core\Models\EZT2\Variable', 'id', 'variable_id');
    }
    
}