<?php

/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/24/16
 * Time: 3:10 PM
 */
namespace App\Core\Models\EZT2\User\Project;

use App\Core\Traits\HasCompositePrimaryKey;
use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\Design\Customizable\Design;

class CustomizableDesign extends BaseModel
{
    use HasCompositePrimaryKey;
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    protected $primaryKey = [
        'user_project_id',
        'customizable_design_id'
    ];
    public $incrementing = false;

    public $timestamps = false;

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'user_project_customizable_design';


    /**
     * Establish a relationship between the customizable design and the design.
     *
     * @return mixed
     */
    public function design()
    {
        $compositeKey = array(
            'id' => $this->customizable_design_id  
        );
        
        return Design::where($compositeKey)->first();
    }
    
}