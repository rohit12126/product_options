<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/24/16
 * Time: 3:12 PM
 */

namespace App\Core\Models\EZT2\Design\Customizable;

use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\TemplateVar;

class Design extends BaseModel
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
    protected $table = 'customizable_design';


    /**
     * Get the template variables for the design's template.
     *
     * @return mixed
     */
    public function getVariables()
    {
        $template = $this->getTemplateFromStatus();
        return Variable::whereRaw(
            'customizable_design_id = ? AND instance_id = ?', 
            [$this->id, $template->id]
        )->get();
    }

    /**
     * Get the template that the design is based on.
     * If the  design is live, get the live template instance.
     * Otherwise, get the template instance that is not hidden.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    protected function getTemplateFromStatus()
    {
        if ('live' == $this->design_status) {
            return $this->hasMany('App\Core\Models\EZT2\Template\Instance', 'id', 'template_instance')
                ->where('status', 'live')
                ->orderBy('status', 'DESC')
                ->first();
        } else {
            return $this->hasMany('App\Core\Models\EZT2\Template\Instance', 'id', 'template_instance')
                ->where('status', '!=', 'hidden')
                ->orderBy('status', 'ASC')
                ->first();
        }

    }

    /**
     * Get a specific variable for a customizable design.
     *
     * @param $variableId
     * @return mixed
     */
    public function getCustomizableDesignVariable($variableId)
    {
        $template = $this->getTemplateFromStatus();
        return Variable::whereRaw(
            'customizable_design_id = ? AND instance_id = ? AND variable_id = ?',
            [$this->id, $template->id, $variableId]
        )->first();
    }

    /**
     * Get all of the variables for a customizable design.
     *
     * @return mixed
     */
    public function getCustomizableDesignVariables()
    {
        $template = $this->getTemplateFromStatus();
        return TemplateVar::whereRaw(
            'template_instance_id = ?',
            [$template->id]
        )->get();
    }
}