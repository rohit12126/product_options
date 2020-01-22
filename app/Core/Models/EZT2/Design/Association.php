<?php

namespace App\Core\Models\EZT2\Design;
use App\Core\Models\BaseModel;
use App\Core\Traits\HasCompositePrimaryKey;

class Association extends BaseModel
{
    use HasCompositePrimaryKey;
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    /**
     * @var string
     */
    protected $table = 'designAssociation';

    /**
     * The primary key for the model.
     *
     * @var mixed
     */
    protected $primaryKey = [
        'child_design_id',
        'parent_design_id',
        'pageNum',
        'associationType',
        'display_order'
    ];

    public $incrementing = false;

    public $timestamps = false;
}