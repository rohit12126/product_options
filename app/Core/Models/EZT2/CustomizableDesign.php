<?php

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\Design\Association;
use App\Core\Models\EZT2\Design\Customizable\Variable as CustomizableVariable;
use App\Core\Models\EZT2\Template\Instance;
use App\Core\Models\OrderCore\PulseLayout;

class CustomizableDesign extends BaseModel
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
    protected $table = 'customizable_design';

    /**
     * Get the suggested designs for a given side.
     * For example: a suggested back to a front.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function suggestedDesigns()
    {
        return $this->belongsToMany(CategoryDesign::class, 'designAssociation', 'parent_design_id', 'child_design_id')
            ->where('associationType', 'suggested');
    }

    /**
     * Get the template variables that belong to a design's template.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariables()
    {
        $template = $this->getTemplateFromStatus();
        return $template->hasMany(TemplateVar::class, 'template_instance_id',
                                         'id')->with('variable')->get();
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
            return $this->hasMany(Instance::class, 'id', 'template_instance')
                ->where('status', 'live')
                ->orderBy('status', 'DESC')
                ->first();
        } else {
            return $this->hasMany(Instance::class, 'id', 'template_instance')
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
        return CustomizableVariable::whereRaw(
            'customizable_design_id = ? AND instance_id = ? AND variable_id = ?',
            [$this->id, $template->id, $variableId]
        )->first();
    }

    /**
     * Get the just sold postcard from the same group.
     *
     * @param int $prodPrintId
     * @return mixed
     */
    public function justSold($prodPrintId = 2)
    {
        return PulseLayout::join('ezt2.customizable_design as cd', function ($join) use ($prodPrintId) {
            $join->on('cd.id', '=', 'pulse_layout.design_id')
                ->where('pulse_layout.group_id', $this->group_id)
                ->where('pulse_layout.product_print_id', $prodPrintId)
                ->where('pulse_layout.type', 'just sold');
        })->first();
    }

    /**
     * Get a collection of customizable designs, based on a category and product print ID.
     * Optionally, provide a page count for pagination.
     *
     * @param integer $catId
     * @param integer $whereProductPrintId
     * @param integer $pageCount
     * @return mixed
     */
    public static function getCustomizableDesigns($catId, $whereProductPrintId, $pageCount = null)
    {
        $customizableDesigns = CustomizableDesign::join(
            'group_category_layout_xref as gclx',
            function ($join) use ($catId, $whereProductPrintId)
            {
                $join->on('customizable_design.layout_id', '=', 'gclx.layout_id')
                    ->where('gclx.group_category_id', '=', $catId)
                    ->where('customizable_design.product_print_id', '=', $whereProductPrintId)
                    ->where('customizable_design.design_status', '=', 'live');
            })
            ->join('layout as l', 'l.layout_id', '=', 'gclx.layout_id')
            ->join('order_core.pulse_layout as pl', 'pl.design_id', '=', 'customizable_design.id')
            ->with('suggestedDesigns')
            ->select('customizable_design.*', 'l.layout_name', 'pl.group_id')
            ->orderBy('gclx.display_order', 'asc')
            ->orderBy('l.layout_name', 'asc')
            ->orderBy('l.layout_id', 'asc');

        return (
            null !== $pageCount ?
                $customizableDesigns->paginate($pageCount) :
                $customizableDesigns->get()
        );
    }
}