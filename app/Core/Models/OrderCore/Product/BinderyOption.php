<?php

namespace App\Core\Models\OrderCore\Product;

use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\Product;


class BinderyOption extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Turn off timestamps
     */
    public $timestamps = false;

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'product_bindery_option';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    // public function product(){
    //     return $this->hasMany(Product::class);
    // }

    
    public function dependentBinderyOption(){
        return $this->belongsTo('App\Core\Models\OrderCore\BinderyOption','dependent_bindery_option_id');
    }


}
