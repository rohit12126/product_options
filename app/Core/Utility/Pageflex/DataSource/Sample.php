<?php
namespace App\Core\Utility\Pageflex\DataSource;
/**
 * Pageflex and Mpower API
 * @package PageflexPHP
 * @version $Id$
 */

/**
 * PageFlex Data source sampler
 *
 * Caches records for which fields have either minimum or maximum length
 *
 * Should prove useful to create proofing samples
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 * @since 1.1
 * @ignore
 */
class Sample
{
// Number of fields in
    private $_numFields;
    // For each of the fields, minimum length and pointer to
    // record in recordCache
    // Array indexed on fieldnr. Each of the entries is a
    // two element array. The first element contains the minimum length
    // the second an index into $recordCache
    private $_minVals;
    // See minVals
    private $_maxVals;
    // Cached records. Array with 2 elements. First is a use count
    // (number of min/maxVals pointing to this record). Second is an array with
    // the field values (indexed on field index)
    private $_recordCache;

    /**
     * Constructor
     *
     * @param int $numFields Number of fields in the record
     * for which the cache will be build.
     *
     */
    public function __construct($numFields)
    {
        $this->_numFields = $numFields;
        $this->_minVals = array($this->_numFields);
        $this->_maxVals = array($this->_numFields);
        for ($ix = 0; $ix < $this->_numFields; $ix++) {
            $this->_minVals[$ix] = array(2147483647, -1);
            $this->_maxVals[$ix] = array(-1, -1);
        }
    }

    /**
     * Add record to cache if any of its fields lengths are either
     * longer of smaller than the previously cached record with
     * min and max length values.
     *
     * @param array $record New record to consider for caching
     *
     * @throws \Exception
     */
    public function newRecord($record)
    {

        // Make sure field counts match
        if (count($record) != $this->_numFields) {
            throw new \Exception(
                sprintf(
                    "[PFDatasourceSample:newRecord]" .
                    "Expected field count(%d)" .
                    " does not match field count (%d) in provided record",
                    $this->_numFields, count($record)
                )
            );
        }

        //Where is current record in cache - obviously, right now, nowhere
        $currAt = -1;

        for ($ix = 0; $ix < $this->_numFields; $ix++) {

            // Minimum length cache for current field

            if ($this->_minVals[$ix][0] > strlen($record[$ix])) {
                // New minimum!
                // First decrease use count of old minimum
                if ($this->_minVals[$ix][1] != -1) {
                    $this->_recordCache[$this->_minVals[$ix][1]][0]--;
                    if ($this->_recordCache[$this->_minVals[$ix][1]][0] == 0) {
                        // No more reference to this cached record - remove it
                        $this->_recordCache[$this->_minVals[$ix][1]] = null;
                    }
                }

                if ($currAt == -1) {
                    // Add to cache
                    $this->_recordCache[] = array(1, $record);
                    $currAt = count($this->_recordCache) - 1;
                } else {
                    // Already in cache - increase use count
                    $this->_recordCache[$currAt][0]++;
                }
                // Set min val
                $this->_minVals[$ix][0] = strlen($record[$ix]);
                $this->_minVals[$ix][1] = $currAt;

            }


            // Maximum length cache for current field

            if ($this->_maxVals[$ix][0] < strlen($record[$ix])) {
                // New maximum!
                // First decrease use count of old maximum
                if ($this->_maxVals[$ix][1] != -1) {
                    $this->_recordCache[$this->_maxVals[$ix][1]][0]--;
                    if ($this->_recordCache[$this->_maxVals[$ix][1]][0] == 0) {
                        // No more reference to this cached record - remove it
                        $this->_recordCache[$this->_maxVals[$ix][1]] = null;
                    }
                }

                if ($currAt == -1) {
                    // Add to cache
                    $this->_recordCache[] = array(1, $record);
                    $currAt = count($this->_recordCache) - 1;
                } else {
                    // Already in cache - increase use count
                    $this->_recordCache[$currAt][0]++;
                }
                // Set min val
                $this->_maxVals[$ix][0] = strlen($record[$ix]);
                $this->_maxVals[$ix][1] = $currAt;

            }


        }

    }

    /**
     * Return cached data
     *
     * @return array Cached records
     *
     */
    public function getCache()
    {
        return $this->_recordCache;
    }
}