<?php
namespace App\Core\Models\OrderCore\Eddm;

use App\Core\Models\BaseModel;

class Selection extends BaseModel
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
    protected $table = 'eddm_selection';

    /**
     * Establish a relationship to the user.
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }   
}