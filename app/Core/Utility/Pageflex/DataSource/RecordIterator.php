<?php
/**
 * Pageflex and Mpower API
 * @package PageflexPHP
 * @version $Id$
 */
namespace App\Core\Utility\Pageflex\DataSource;
/**
 * PageFlex Data source record iterator
 *
 * Iterates over "records". Helper interface for {@link PFDatasource::fromIterator2Mdb()}.
 *
 * Currently two iterator implementations are available.
 * - Tab delimited file iterator
 * - MySQL result set iterator
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 * @since 1.1
 */
interface RecordIterator
{
    /**
     * Get next record from iterator
     *
     * @return mixed Either false or array of fields for the next record
     *
     */
    public function next();

    /**
     * Return array with field names for records retrieved from this iterator.
     *
     */
    public function getFields();
}
