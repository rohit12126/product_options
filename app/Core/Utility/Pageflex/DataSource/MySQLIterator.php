<?php
namespace App\Core\Utility\Pageflex\DataSource;
/**
 * An IPFDatasourceRecordIterator which iterates over a MySQL result set
 *
 * @since 1.1
 *
 */
class MySQLIterator implements RecordIterator
{
// Handle to open MySQL connection
    private $_mysql;
    // Handle to open MySQL query
    private $_query;
    // Field names associated with this iterator
    private $_fieldNames;

    /**
     * Create PFDatasourceMySQLIterator
     *
     * @param array $connectInfo 6 element array containing host,user,password,database, port and socket information
     * @param string $sql SQL statement over whose result set will be iterated with the next() method.
     * @throws \Exception
     */
    public function __construct($connectInfo, $sql)
    {

        if (count($connectInfo) == 4) {
            // Ok, be nice to the user
            list($host, $user, $password, $db) = $connectInfo;
            $port = null;
            $socket = null;
        } else {
            if (count($connectInfo) == 6) {
                list($host, $user, $password, $db, $port, $socket) = $connectInfo;
            } else {
                throw new
                \Exception(
                    sprintf(
                        "[PFDatasourceMySQLIterator:__construct]Specified connection information not correct"
                    )
                );
            }
        }
        // Connect
        $this->_mysql = new \mysqli($host, $user, $password, $db, $port, $socket);
        if (!$this->_mysql) {
            throw new
            \Exception(
                sprintf(
                    "[PFDatasourceMySQLIterator:__construct]MySQL connection failed:%s",
                    mysqli_connect_error()
                )
            );
        }
        // Execute SQL
        $this->_query = $this->_mysql->_query($sql);
        if (!$this->_query) {
            throw new
            \Exception(
                sprintf(
                    "[PFDatasourceMySQLIterator:__construct]SQL execution failed:%s",
                    mysqli_error($this->_mysql)
                )
            );
        }
        //Figure out field names
        $fieldsInfo = $this->_query->fetch_fields();
        if ($fieldsInfo == null) {
            throw new \Exception(
                sprintf(
                    "[PFDatasourceMySQLIterator:__construct]Unable to get field names(%s)",
                    mysqli_error($this->_mysql)
                )
            );
        }
        // Fill local array with field names, replace "null" with colxxxx
        foreach ($fieldsInfo as $fieldInfo) {
            $name = $fieldInfo->name;
            if ($name == null || strlen($name) == 0) {
                $this->_fieldNames[] = "col" . count($this->_fieldNames);
            } else {
                $this->_fieldNames[] = $name;
            }
        }
    }

    /**
     * See {@link IPFDatasourceRecordIterator::getFields()}
     *
     */
    public function getFields()
    {
        return $this->_fieldNames;
    }

    /**
     * See {@link IPFDatasourceRecordIterator::next()}
     *
     */
    public function next()
    {
        $res = $this->_query->fetch_row();
        if ($res == null) {
            $this->_query->free_result();
            $this->_mysql->close();
            return false;
        }
        return $res;
    }
}