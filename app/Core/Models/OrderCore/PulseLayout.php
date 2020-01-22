<?php

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\CategoryDesign;

class PulseLayout extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'pulse_layout';
    
    /**
     * Allow mass assignment all, []
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Define the relationship to CategoryDesign.
     *
     * @return mixed
     */
    public function design()
    {
        return $this->belongsTo(CategoryDesign::class, 'design_id', 'design_id');
    }
}