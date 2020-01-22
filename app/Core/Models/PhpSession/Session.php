<?php

/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/9/16
 * Time: 10:46 AM
 */

namespace App\Core\Models\PhpSession;
use App\Core\Models\BaseModel;

class Session extends BaseModel
{

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'sessions';

    /**route
     * Specify the table to use.
     *
     * @var string
     */

    protected $table = 'session';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that are required.
     *
     * @var array
     */
    protected $_rules = [

    ];

    
    private $_parsedData = [];
    
    /**
     * Specify the database connection to be used for the query.
     *
     * @param $connection
     */
    public function changeConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Used to help bridge the gap between Zend and Laravel sessions.
     * @throws \Exception
     */
    public function parseSessionData()
    {
        $session_data = $this->data;
        $this->_parsedData = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $this->_parsedData[$varname] = $data;
            $offset += strlen(serialize($data));
        }
    }
    
    
    public function getPortalName()
    {
        if (isset($this->_parsedData['portalName'])) {
            return $this->_parsedData['portalName'];
        }
        return '';
    }

}