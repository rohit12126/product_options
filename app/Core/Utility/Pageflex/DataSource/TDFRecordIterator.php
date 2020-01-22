<?php

namespace App\Core\Utility\Pageflex\DataSource;
/**
 * An IPFDatasourceRecordIterator which iterates over the fields of a tab delimited file
 *
 * @since 1.1
 *
 */
class TDFRecordIterator implements RecordIterator
{
// File resource pointing to opened tab delimted file
    private $_fh;
    // Array with field names
    private $_fieldNames;

    /**
     * Create PFDatasourceTDFRecordIterator
     *
     * @param string $tdf Name of tab delimited file
     * @param array $fieldNames Array with names of fields in the tab delimited file
     * @throws \Exception
     */
    public function __construct($tdf, $fieldNames)
    {
        $this->_fh = fopen($tdf, "r");
        if (!$this->_fh) {
            throw new \Exception(sprintf("[PFDatasource::fromTd2Mdb]Failed to open %s.", $tdf));
        }
        $this->_fieldNames = $fieldNames;
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
        if (!feof($this->_fh)) {
            $fl = fgets($this->_fh, 64000);
            $flds = explode("\t", $fl);
            if ((count($flds) == 1 && strlen($flds[0]) == 0)) {
                fclose($this->_fh);
                return false;
            }
            return $flds;
        } else {
            fclose($this->_fh);
            return false;
        }
    }
}