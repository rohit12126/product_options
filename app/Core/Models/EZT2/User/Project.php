<?php

namespace App\Core\Models\EZT2\User;

use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\User\Project\CustomizableDesign;
use App\Core\Models\OrderCore\DesignFile;
use App\Core\Workflow\DesignManager;

class Project extends BaseModel
{
    protected $with = ['customizableDesigns'];

    protected $connection = 'ezt2';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $table = 'user_project';

    /**
     * Establish a relationship between the project and it's designs.
     *
     * @return mixed
     */
    public function customizableDesigns()
    {
        return $this->hasMany(CustomizableDesign::class, 'user_project_id', 'id');
    }

    /**
     * Get the project's designs.
     *
     * @return array
     */
    public function getDesigns()
    {
        $designs = array();
        foreach ($this->customizableDesigns()->get() as $customizableDesign) {
            $designs[$customizableDesign->page] = $customizableDesign->design();
        }
        return $designs;
    }

    /**
     * Establish a relationship between the project and it's design files
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getDesignFiles()
    {
        return $this->hasMany(DesignFile\UserProject::class,
                                     'user_project_id', 'id');
    }

    /**
     * Get the path to the PDF based on the template path.
     *
     * @return mixed
     */
    public function getGeneratedPdf()
    {
        return str_replace('template.pf', 'template.pdf', $this->file_path);
    }

    /**
     * Get the public path to the PDF.
     *
     * @return mixed
     */
    public function getWebPdfPath()
    {
        return str_replace('/share/SANweb/ezt2wwwroot', '/imageserver',$this->getGeneratedPdf());
    }

    /**
     * Clone one project from another.
     *
     * @param Project $oldProject
     * @throws \Exception
     */
    public function cloneFrom(Project $oldProject)
    {
        if ($oldProject->id != $this->id) {
            $dmOld = new DesignManager();
            $dmOld->load($oldProject->id);
            $dmNew = new DesignManager();
            $dmNew->load($this->id);
            $newVariables = [];
            foreach ($dmOld->getVariables() as $section => $variables) {
                foreach ($variables as $key => $variable) {
                    if ($variable->allowUserEdit) {
                        $newVariables[$key] = $variable->value;
                    }
                }
            }
            $dmNew->setVariables($newVariables);
        }
    }
}