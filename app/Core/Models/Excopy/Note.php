<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;

class Note extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'notes';
    protected $guarded = [];
    protected $primaryKey = 'id';
}