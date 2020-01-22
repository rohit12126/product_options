<?php

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;

class States extends BaseModel
{
    protected $connection = 'ezt2';

    protected $primaryKey = 'state_id';

    public $timestamps = false;

    protected $table = 'states';

    protected $guarded = [];

    /**
     * Generate a list of states for use in dropdown menus.
     *
     * @return mixed
     */
    public static function getList()
    {
        $list = self::pluck('state', 'state_id');
        $list->prepend('Choose One', '');

        return $list;
    }
}