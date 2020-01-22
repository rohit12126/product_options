<?php

namespace App\Core\Models\OrderCore\Design;

use App\Core\Models\BaseModel;

class Project extends BaseModel
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
    protected $table = 'design_project';
}