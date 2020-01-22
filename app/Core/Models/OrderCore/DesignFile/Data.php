<?php
namespace App\Core\Models\OrderCore\DesignFile;
use App\Core\Models\BaseModel;

/**
 * order_core.design_file_data is a table for storing non-normalized data for a design file.
 */
class Data extends BaseModel
{
    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    protected $primaryKey = ['design_file_id', 'name'];

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'design_file_data';

    public $timestamps = false;

    public $incrementing = false;
}