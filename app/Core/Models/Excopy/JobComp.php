<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;

class JobComp extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'job_comps';
    protected $guarded = [];
    protected $primaryKey = 'comp_id';
    public $incrementing = false;
}