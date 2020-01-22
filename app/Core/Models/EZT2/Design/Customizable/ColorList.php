<?php

namespace App\Core\Models\EZT2\Design\Customizable;

use App\Core\Models\BaseModel;

class ColorList extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'ezt2';

    protected $primaryKey = 'color_id';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'color_list';

}