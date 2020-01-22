<?php

namespace App\Core\Models\OrderCore\DesignFile;


use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\User\Project;
use App\Core\Models\OrderCore\DesignFile;
use App\Core\Traits\HasCompositePrimaryKey;

class UserProject extends BaseModel
{
    use HasCompositePrimaryKey;
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    public $incrementing = false;

    protected $primaryKey = [
        'user_project_id',
        'design_file_id'
    ];

    protected $fillable = [
        'user_project_id',
        'design_file_id'
    ];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'design_file_user_project';

    public $timestamps = false;


    /**
     * Establish a relationship to one of a user's project's design file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function designFile()
    {
        return $this->belongsTo(DesignFile::class, 'design_file_id', 'id');
    }

    /**
     * Establish a relationship to the user's UserProject.
     * UserProjects represent a user's edits to a design.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userProject()
    {
        return $this->belongsTo(Project::class, 'user_project_id', 'id');
    }

}