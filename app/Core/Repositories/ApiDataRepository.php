<?php

namespace App\Core\Repositories;

use App\Core\Interfaces\ApiDataInterface;
use App\Core\Models\OrderCore\Api\Data;
use App\Core\Repositories\BaseRepository;

class ApiDataRepository extends BaseRepository implements ApiDataInterface 
{
    
    protected $model;
    
    public function __construct(Data $model)
    {
        $this->model = $model;
    }
}