<?php

namespace App\Core\Repositories;


use App\Core\Interfaces\ContactInterface;
use App\Core\Models\OrderCore\Data;
use App\Core\Repositories\BaseRepository;

class ContactRepository extends BaseRepository implements ContactInterface
{
    protected $model;
    
    public function __construct(Data $model)
    {
        $this->model = $model;
    }
    public function setSampleData($user){
        $this->model->create([
            'name'=> 'samplePacketRequest',
            'value' => json_encode($user)
        ]);  
    }
}