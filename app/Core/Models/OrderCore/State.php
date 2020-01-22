<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 4/25/16
 * Time: 10:53 AM
 */

namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class State extends BaseModel
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
    protected $table = 'state';

    /**
     * Generate a list of states for use in dropdown menus.
     *
     * @param bool $abbreviated
     * @return mixed
     */
    public static function getList($abbreviated = false)
    {
        if ($abbreviated) { //Imprev uses the abbreviated list
            $list = self::pluck('abbrev', 'abbrev');
            $list->prepend('State', '');
        } else { //Pulse/excopy
            $list = self::pluck('name', 'id');
            $list->prepend('Choose One', '');
        }

        return $list;
    }
}