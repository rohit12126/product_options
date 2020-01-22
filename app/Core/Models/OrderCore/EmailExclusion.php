<?php
namespace App\Core\Models\OrderCore;

use App\Core\Models\BaseModel;

class EmailExclusion extends BaseModel
{
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $connection = 'order_core';

    protected $table = 'email_exclusion';

    protected $guarded = [];

    /**
     * @param mixed $value
     * @return BaseModel|void
     */
    public function setCreatedAt($value)
    {
        ;
    }
}