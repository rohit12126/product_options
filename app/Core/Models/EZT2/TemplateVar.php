<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 7/1/16
 * Time: 9:44 AM
 */

namespace App\Core\Models\EZT2;

use App\Core\Models\BaseModel;

class TemplateVar extends BaseModel
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
    protected $table = 'template_vars';

    /**
     * Establish a relationship to the variable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function variable()
    {
        return $this->hasOne('App\Core\Models\EZT2\Variable', 'id', 'var_id');
    }

}