<?php

namespace App\Core\Utility\Pageflex;
    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     * @version $Id$
     */
use App\Core\Utility\Pageflex\DataSource\RecordIterator;
use App\Core\Utility\Pageflex\DataSource\TDFRecordIterator;
use App\Core\Utility\Pageflex\DataSource\Sample;

/**
 * PageFlex Data source
 *
 * Exposes the properties of a Pageflex studio datasource.
 *
 * A utility method ({@link fromIterator2Mdb()}) allows creating and populating
 * a Microsoft Access .mdb file which can then be used as input to Mpower.
 * To support this method, there are currently two different iterator implementations
 * available ({@link TDFRecordIterator} and {@link MySQLIterator}.
 * The first takes as input a tab delimted file and a column name
 * array. The second accepts MySQL connection informationand a SQL statement
 * that will be executed and whose results will be used to populate the .mdb file.
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 */

class DataSource extends Object
{
// Name of this datasource
    private $_name;
    // Data file associated with this datasource
    private $_dbFile;
    // DB login associated with this datasource
    private $_dbLogin;
    // DB table associated with this datasource
    private $_dbTable;
    // Key field in dbTable
    private $_key;
    // Password property
    private $_password;


    /**
     * Create a datasource from an array of Pageflex datasource property values.
     * <br/>Properties should be named as they are serialized in the Pageflex .pf project file
     * <br/>The following properties are recognized
     * - name - datasource name
     * - db_filepath - filepath to datasource file in case of a file based datasource
     * - login - login information
     * - ds_table - table associated with datasource
     * - key - key field in ds_table (if any)
     * - password - password propertys
     *
     * @param array $properties Array of property values indexed on property name
     *
     * @since 1.3
     */
    public function __construct($properties = null)
    {
        if ($properties != null) {
            $this->_name = isset($properties["name"]) ? $properties["name"] : null;
            $this->_dbFile = isset($properties["db_filepath"]) ? $properties["db_filepath"] : null;
            $this->_dbLogin = isset($properties["login"]) ? $properties["login"] : null;
            $this->_dbTable = isset($properties["ds_table"]) ? $properties["ds_table"] : null;
            $this->_key = isset($properties["key"]) ? $properties["key"] : null;
            $this->_password = isset($properties["password"]) ? $properties["password"] : null;
        }
    }


    /**
     * Set name of this datasource
     * @param string $name name of this datasource
     *
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Return name of this datasource
     *
     * @return string  name of the datasource
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set database file containing the data of this datasource
     *
     * @param string $dbFile Database file containing the data of this datasource
     *
     */
    public function setDbFile($dbFile)
    {
        $this->_dbFile = $dbFile;
    }

    /**
     * Return database file containing the data of this datasource
     *
     * @return string Database file containing the data of this datasource
     *
     */
    public function getDbFile()
    {
        return $this->_dbFile;
    }

    /**
     * Set database login information for database containing the data of this datasource
     *
     * @param string $dbLogin Login information
     */
    public function setDbLogin($dbLogin)
    {
        $this->_dbLogin = $dbLogin;
    }

    /**
     * Return database login information for database containing the data of this datasource
     *
     * @return string Login information
     *
     */
    public function getDbLogin()
    {
        return $this->_dbLogin;
    }

    /**
     * Set database password information for database containing the data of this datasource
     *
     * @param string $password information (should be encrypted the way e.g. Pageflex studio encrypts the ODBC password)
     * @since 1.3
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }

    /**
     * Return database password  for database containing the data of this datasource
     *
     * @return string Password (encrypted the way e.g. Pageflex studio encrypts the ODBC password)
     * @since 1.3
     *
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Set database table containing the data of this datasource
     *
     * @param string $dbTable Table name containing data of this datasource
     *
     */
    public function setDbTable($dbTable)
    {
        $this->_dbTable = $dbTable;
    }

    /**
     * Return database table containing the data of this datasource
     *
     * @return string Table name containing data of this datasource
     *
     */
    public function getDbTable()
    {
        return $this->_dbTable;
    }

    /**
     * Set table key column name.
     *
     * @param string $key Key column name
     *
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * Return table key column name.
     *
     * @return string Key column name
     *
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Copy data from a tab delimited data file to an MSAccess .mdb file
     *
     * Note that the $mdb argument will be used to update the dbFile property {@link setDbFile()} of the current
     * datasource object.
     *
     * @param string $tdf Name of tab delimited file containing the data
     * @param array $colNames Array with names of table columns for each of the fields in the tab delimited file
     * @param string $emptyMdb Name of a file containing an empty MSAccess database file
     * (unfortunately such an empty mdb file can not be easily created from within php)
     * @param string $mdb MSAccess database file to wich the tab delimited data has to be copied
     * @param string $proofTable MSAccess table that will contain proofing data. If null, no proofing table is created.
     *
     * @deprecated Use PFDatasource::fromIterator2Mdb(new PFDatasourceTDFRecordIterator($tdf,$colnames),...)
     *
     * @throws \Exception
     */
    public function fromTd2Mdb($tdf, $colNames, $emptyMdb, $mdb, $proofTable = null)
    {
        $this->fromIterator2Mdb(
            new TDFRecordIterator($tdf, $colNames), $emptyMdb, $mdb, $proofTable
        );
    }


    /**
     * Copy data from a PFDatasourceRecordIterator to an MSAccess .mdb file
     *
     * Note that the $mdb argument will be used to update the dbFile property {@link setDbFile()} of the current
     * datasource object.
     *
     * @param RecordIterator $recordIterator Iterator over records with data to insert in mdb file
     * @param string $emptyMdb Name of a file containing an empty MSAccess database file
     * (unfortunately such an empty mdb file can not be easily created from within php)
     * @param string $mdb MSAccess database file to wich the tab delimited data has to be copied
     * @param string $proofTable MSAccess table that will contain proofing data {@link PFDatasourceSample}.
     * If null, no proofing table is created.
     *
     * @throws \Exception
     * @since 1.1
     *
     */
    public function fromIterator2Mdb(RecordIterator $recordIterator, $emptyMdb, $mdb, $proofTable = null)
    {
        // Need a empty mdb file.
        if ($emptyMdb == null) {
            throw new \Exception("[PFDatasource::fromIterator2Mdb]Missing empty mdb file.");
        }
        // Copy empty mdb file
        if (!copy($emptyMdb, $mdb)) {
            throw new \Exception(
                sprintf("[PFDatasource::fromIterator2Mdb]Failed to copy %s to %s", $emptyMdb, $mdb)
            );
        }
        // Open new mdb database and create table for datafile
        $cs = "Driver={Microsoft Access Driver (*.mdb)};DBQ=" . $mdb;
        $odbcH = odbc_connect($cs, null, null);
        if ($odbcH == null) {
            throw new \Exception(
                sprintf("[PFDatasource::fromIterator2Mdb]Failed to open %s. %s", $mdb, odbc_errormsg())
            );
        }
        // Build create table and first part of insert command
        $cts = "create table " . $this->_dbTable . " (";
        if ($proofTable != null) {
            $ctsP = "create table " . $proofTable . " (";
        }
        if ($this->_key != null && strlen($this->_key) > 0) {
            $cts .= $this->_key . " counter not null primary key,";
            if ($proofTable != null) {
                $ctsP .= $this->_key . " counter not null primary key,";
            }
        }
        $ins1 = "insert into  " . $this->_dbTable . " ( ";
        if ($proofTable != null) {
            $ins1P = "insert into  " . $proofTable . " ( ";
        }
        $colNames = $recordIterator->getFields();
        foreach ($colNames as $i => $colName) {
            $cts .= $colName . " varchar(255)";
            $ins1 .= $colName;
            if ($proofTable != null) {
                $ctsP .= $colName . " varchar(255)";
                $ins1P .= $colName;
            }
            if ($i + 1 < count($colNames)) {
                $cts .= ",";
                $ins1 .= ',';
                if ($proofTable != null) {
                    $ctsP .= ",";
                    $ins1P .= ',';
                }
            }
        }
        $cts .= ")";
        $ins1 .= ") ";
        if ($proofTable != null) {
            $ctsP .= ")";
            $ins1P .= ") ";
        }
        // Create table
        if (!odbc_exec($odbcH, $cts)) {
            throw new \Exception(
                sprintf(
                    "[PFDatasource::fromIterator2Mdb]Create failed. %s returned %s",
                    $cts, odbc_errormsg($odbcH)
                )
            );
        }

        // Insert a row for each record retrieved from the iterator
        //   Note - tried to used a prepared statement,
        //   but there are problems with how php binds parameters to the driver.
        //          ==> (had to) switch(ed) to exec direct.
        //
        $line = 0;
        $numCols = count($colNames);

        //Proofing table
        if ($proofTable != null) {
            $pfdatasourceSample = new Sample($numCols);
        }
        while ($flds = $recordIterator->next()) {
            $line++;
            if (count($flds) != $numCols) {
                throw new \Exception(
                    sprintf(
                        "[PFDatasource::fromIterator2Mdb]Datafile line" .
                        "%d has %d fields where %d where expected",
                        $line, count($flds), count($colNames)
                    )
                );
            }
            $ins = $ins1 . " values (";
            $fldVals = array();
            foreach ($flds as $i => $fld) {
                if ($fld != null) {
                    $fldVal = preg_replace('/\'/', '\'\'', $fld);
                    $ins .= '\'' . $fldVal . '\'';
                    $fldVals[] = $fldVal;
                } else {
                    $ins .= "null";
                    $fldVals[] = "";
                }
                if ($i + 1 < count($flds)) {
                    $ins .= ",";
                }
            }
            $ins .= ")";


            if (!odbc_exec($odbcH, $ins)) {
                throw new \Exception(
                    sprintf(
                        "[PFDatasource::fromIterator2Mdb]Insert failed. %s returned %s",
                        $ins,
                        odbc_errormsg($odbcH)
                    )
                );
            }

            if ($proofTable != null) {
                $pfdatasourceSample->newRecord($fldVals);
            }

        }

        // Proofing table - creating and population
        if ($proofTable != null) {
            // Create table
            if (!odbc_exec($odbcH, $ctsP)) {
                throw new \Exception(
                    sprintf(
                        "[PFDatasource::fromIterator2Mdb]Create failed. %s returned %s",
                        $ctsP,
                        odbc_errormsg($odbcH)
                    )
                );
            }

            // Insert
            $proofCache = $pfdatasourceSample->getCache();
            foreach ($proofCache as $cachedRecordInfo) {
                if ($cachedRecordInfo != null) {
                    $flds = $cachedRecordInfo[1];
                    $insP = $ins1P . " values (";
                    foreach ($flds as $i => $fld) {
                        if ($fld != null) {
                            $fldVal = preg_replace('/\'/', '\'\'', $fld);
                            $insP .= '\'' . $fldVal . '\'';
                            $fldVals[] = $fldVal;
                        } else {
                            $insP .= "null";
                            $fldVals[] = "";
                        }
                        if ($i + 1 < count($flds)) {
                            $insP .= ",";
                        }
                    }
                    $insP .= ")";

                    if (!odbc_exec($odbcH, $insP)) {
                        throw new \Exception(
                            sprintf(
                                "[PFDatasource::fromIterator2Mdb]Insert failed. %s returned %s",
                                $ins, odbc_errormsg($odbcH)
                            )
                        );
                    }
                }
            }
        }

        // Done
        odbc_close($odbcH);
        $this->_dbFile = $mdb;
    }


    /**
     * Load datasource information from DOM element
     *
     * @param \DOMElement $dataSourceElement
     * @throws \Exception
     * @ignore
     *
     */
    public function fromdom(\DOMElement $dataSourceElement)
    {
        $this->_name = null;
        if ($dataSourceElement->hasAttribute("name")) {
            $this->_name = $dataSourceElement->getAttribute("name");
        } else {
            throw new \Exception("[PFDatasource::fromdom]Data source does not have name attribute");
        }
        $this->_dbFile = null;
        if ($dataSourceElement->hasAttribute("db_filepath")) {
            $this->_dbFile = $dataSourceElement->getAttribute("db_filepath");
        }
        $this->_key = null;
        if ($dataSourceElement->hasAttribute("key")) {
            $this->_key = $dataSourceElement->getAttribute("key");
        }
        $this->_dbLogin = null;
        if ($dataSourceElement->hasAttribute("login")) {
            $this->_dbLogin = $dataSourceElement->getAttribute("login");
        }
        $this->_password = null;
        if ($dataSourceElement->hasAttribute("password")) {
            $this->_password = $dataSourceElement->getAttribute("password");
        }
        $dsTableNL = $dataSourceElement->getElementsByTagName("ds_table");
        if (count($dsTableNL) != 1) {
            throw new \Exception(
                sprintf(
                    "[PFDatasource::fromdom]Unsupported data source. Found %d ds_table children",
                    count($dsTableNL)
                )
            );
        }
        $this->_dbTable = $dsTableNL->item(0)->nodeValue;
    }


    /**
     * Convert datasource information to DOM element and hook it up under the given parent element
     *
     * @param \DOMElement $parent Parent under which to create DOM element for this datasource
     *
     * @ignore
     *
     */
    public function toDom(\DOMElement $parent)
    {
        // <data_source>
        $dataSourceEl = $parent->ownerDocument->createElement("data_source");
        $parent->appendChild($dataSourceEl);
        $dataSourceEl->setAttribute("name", $this->_name);
        if ($this->_dbFile) {
            $dataSourceEl->setAttribute("db_filepath", $this->_dbFile);
        }
        if ($this->_key) {
            $dataSourceEl->setAttribute("key", $this->_key);
        }
        if ($this->_dbLogin) {
            $dataSourceEl->setAttribute("login", $this->_dbLogin);
        }
        if ($this->_password) {
            $dataSourceEl->setAttribute("password", $this->_password);
        }
        if ($this->_dbTable) {
            $dsTableEl = $parent->ownerDocument->createElement("ds_table", $this->_dbTable);
            $dataSourceEl->appendChild($dsTableEl);
        }

    }
}