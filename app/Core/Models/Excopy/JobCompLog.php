<?php
namespace App\Core\Models\Excopy;

use App\Core\Models\BaseModel;

class JobCompLog extends BaseModel
{
    public $timestamps = false;
    protected $connection = 'excopy';
    protected $table = 'job_comp_log';
    protected $guarded = [];
    protected $primaryKey = 'comp_id';
    public $incrementing = false;
}