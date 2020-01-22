<?php

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;
use Illuminate\Support\Facades\DB;

class CategoryDesign extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    /**
     * Specify the table to use.
     * category_designs represents a design, no category, no linking, just a design.
     *
     * @var string
     */
    protected $table = 'category_designs';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'design_id';

    /**
     * Define a relationship to the customizable design.
     *
     * @return mixed
     */
    public function customizableDesign()
    {
        return $this->belongsTo(CustomizableDesign::class, 'design_id', 'id');
    }

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
     * Get a list of designs for display on frontend.
     *
     * @param $categoryId
     * @param $sizeId
     * @return mixed
     */
    public static function getDesigns($categoryId, $sizeId)
    {
        $config = config('app.server_config.pcp');
        return self::join('ezt2.group_category_layout_xref as gclx', function ($join1) use ($categoryId) {
            $join1->on('category_designs.layout_id', '=', 'gclx.layout_id')
                ->where('gclx.group_category_id', '=', DB::raw($categoryId));
        })->join('ezt2.designAssociation as da', function ($join2) use ($sizeId, $config) {
                $join2->on('category_designs.design_id',  '=',  'da.parent_design_id')
                    ->where('category_designs.group_id',  '=', DB::raw($config['defaultDesignCategoryId']))
                    ->where('category_designs.design_status', '=', 'live')
                    ->where('category_designs.side', '=', 'front')
                    ->where('category_designs.design_size', '=', $sizeId);
        })->groupBy('category_designs.design_id')
            ->paginate($config['paginationCount']);
    }
}