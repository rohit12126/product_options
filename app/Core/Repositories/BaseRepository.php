<?php

namespace App\Core\Repositories;

abstract class BaseRepository {

    /**
     * Eloquent model
     */
    protected $model;

    /**
     * @param $model
     */
    function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Fetch a record by id
     *
     * @param $id
     * @return mixed
     */
    public function find($id)
    {       
        return $this->model->find($id);
    }
   
    /**
     * Get all instances of model
     *     
     * @return mixed
     */
    public function all()
    {
        return $this->model->all();
    }
    
    /**
     * create a new record in the database
     *
     * @param $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * create a new record in the database
     *     
     * @return mixed
     */
    public function save()
    {
        return $this->model->save();
    }
    
    /**
     * update record in the database
     *
     * @param $data
     * @param $id
     * @return mixed
     */
    public function update(array $data, $id)
    {
        $record = $this->find($id);
        return $record->update($data);
    }
    
    /**
     * remove record from the database
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }
   
    /**
     * show the record with the given id
     *
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return $this->model->findOrFail($id);
    }
   
    /**
     * Get the associated model
     *     
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }
   
    /**
     * Set the associated model
     *     
     * @return mixed
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }
   
    /**
     * Eager load database relationships
     *
     * @param $relations
     * @return mixed
     */
    public function with($relations)
    {
        return $this->model->with($relations);
    }

}