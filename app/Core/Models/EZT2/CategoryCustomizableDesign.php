<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/1/16
 * Time: 4:42 PM
 */

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\PulseLayout;

class CategoryCustomizableDesign extends BaseModel
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
    protected $table = 'category_customizable_design';

    /**
     * Define a relationship to the CustomizableDesign.
     *
     * @return mixed
     */
    public function design()
    {
        return $this->belongsTo(CustomizableDesign::class, 'design_id', 'id');
    }

    /**
     * Define a relationship to the pulse_layout table..
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pulseLayout()
    {
        return $this->hasOne(PulseLayout::class, 'design_id', 'design_id');
    }
}