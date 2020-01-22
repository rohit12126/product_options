<?php

namespace App\Core\Utility\Pageflex;
    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     */


/**
 * Pageflex job
 *
 * The Job represents a Pageflex job as one can create them in Pageflex Studio.
 *
 * Because of the wide range of job types Pageflex Studio
 * supports (and the associated complexity of interpreting the
 * .pf file) PFJob objects come in two kinds.
 *
 * When loading a project file using {@link Project::__construct()}
 * and using an existing project file for the $pfProject argument,
 * the job's type is set to OPAQUE (see {@link getType()} and
 * {@link OPAQUE}). Such PFJob objects can only be used to submit
 * for rendering using {@link MPServer::submit()}
 * but none of its settings can be changed.
 *
 * Alternatively, it is possible to create a PFJob object programmatically
 * ({@link __construct()} and specifying one of {@link JPEG} or {@link PDF}
 * for the $type parameter. These jobs can (obviously) also be submitted
 * for rendering using ({@link MPServer::submit()}). JPEG jobs should be
 * used for low res web display while
 * PDF jobs should be used for hires proofing.
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 * @version 1.7
 *
 */

class Job extends Object
{
    // Utility constant indicating type of job
    // - OPAQUE means it was read from an input .pf file
    // - JPEG means , created by the user requesting jpeg output
    // - PDF means, created by the user requesting pdf output

    /**
     * Job was loaded from a project file (should not be used in {@link PFJob::__construct()}.
     */
    const OPAQUE = 1;
    /**
     * When submitting a job of this type, a JPEG rendering will be created.
     */
    const JPEG = 2;
    /**
     * When submitting a job of this type, a PDF rendering will be created.
     */
    const PDF = 3;
    // Name of this Pageflex job
    private $_jobName;
    // Name of the template associated with this job
    private $_templateName;
    // Type of job (see const's higher)
    private $_type;
    // DOM element in case of an OPAQUE PFJob instance. Set when loading from DOM
    private $_domElement;
    // Pageflex datasource to which this job refers
    private $_pfDatasource;
    // Optional query to select a subset of the datasource's rows.
    private $_pfQuery;
    // Pageflex job attribute 'adjustcode' which specifies how to adjust the output of a particular job
    private $_pfAdjustcode;
    // Pageflex job attribute 'size' used with the 'adjustcode' to determine how to resize the output of the job
    private $_pfOutputSize;
    // Pageflex job attribute pagerange, which specifies which pages to render in the document
    private $_pfPageRange;
    //Pageflex job attribute jpeg_quality, which specifies the resolution of the jpg being rendered
    private $_pfJpgQuality;

    /**
     * Constructor
     *
     * @param string $jobName Name of the job
     * @param string $templateName Name of the template to associate with this job
     * @param DataSource $pfDatasource Datasource to associate with this job
     * @param int $type Type of the job (either PFJob::JPEG or PFJob::PDF)
     *
     */
    public function __construct
    ($jobName = null, $templateName = null, $pfDatasource = null, $type = self::OPAQUE)
    {
        $this->_type = false;
        $this->_jobName = $jobName;
        $this->_templateName = $templateName;
        $this->_pfDatasource = $pfDatasource;
        $this->_type = $type;
    }

    /**
     * Return name of job
     *
     * @return string Name of the job
     *
     */
    public function getName()
    {
        return $this->_jobName;
    }

    /**
     * Set name of job
     *
     * @param string $name Name of the job
     *
     */
    public function setName($name)
    {
        $this->_jobName = $name;
    }

    /**
     * Load job information from DOMElement
     *
     * Currently no attempt is made to interpret the DOM element's content.
     *
     * It is just stored and the job's type is set to OPAQUE
     *
     * @ignore
     * @param \DOMElement $jobElement
     */
    public function fromDom(\DOMElement $jobElement)
    {
        $this->_type = self::OPAQUE;
        $this->_domElement = $jobElement;
        $this->_jobName = $this->_domElement->getAttribute("name");
    }

    /**
     * Set SQL query associated with this job. Useful to restrict amount of documents to render.
     *
     * @param string $pfQuery The SQL query
     */
    public function setQuery($pfQuery)
    {
        $this->_pfQuery = $pfQuery;
    }

    /**
     * Return SQL query associated with this job. Useful to restrict amount of documents to render.
     *
     * @return string
     * @internal param string $pfQuery The SQL query
     */
    public function getQuery()
    {
        return $this->_pfQuery;
    }

    /**
     * Return type of job object.
     *
     * Possible returned values are PFJob::OPAQUE, PFJob::JPEG or PFJob::PDF
     *
     * PFJob::OPAQUE refers to a job that was loaded from a .pf file.
     *
     * @return integer Type of job.
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set type of job object.
     *
     * Allowed values are PFJob::JPEG or PFJob::PDF
     *
     * @param integer $type Type of job.
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * Set adjustCode
     * @param $adjustCode
     */
    public function setAdjustCode($adjustCode)
    {
        $this->_pfAdjustcode = $adjustCode;
    }

    /**
     * Set size percentage
     * @param $sizePercentage
     */

    public function setSize($sizePercentage)
    {
        $this->_pfOutputSize = $sizePercentage;
    }

    /**
     * set pagerange
     * @param $pageRange
     */

    public function setPageRange($pageRange)
    {
        $this->_pfPageRange = $pageRange;
    }

    /**
     * set jpg quality
     * @param $jpgQuality
     */

    public function setPFJpgQuality($jpgQuality)
    {
        $this->_pfJpgQuality = $jpgQuality;
    }


    /**
     * Create DOM Element under the given $parent
     *
     * @ignore
     * @param \DOMElement $parent
     * @throws \Exception
     */
    public function toDom(\DOMElement $parent)
    {
        if ($this->_type == self::OPAQUE) {
            $parent->appendChild($parent->ownerDocument->importNode($this->_domElement, true));
        } else {
            if ($this->_type == self::JPEG) {
                $jobEl = $parent->ownerDocument->createElement("job");
                $parent->appendChild($jobEl);
                $jobEl->setAttribute("name", $this->_jobName);
                $jobEl->setAttribute("template", $this->_templateName);

                $jobInOptionsEl = $parent->ownerDocument->createElement("job_in_options");
                $jobEl->appendChild($jobInOptionsEl);

                if ($this->_pfDatasource != null) {
                    $jobInOptionsEl->setAttribute("data_source", $this->_pfDatasource->getName());
                    if ($this->_pfQuery != null && strlen($this->_pfQuery) > 0) {
                        $jobInOptionsEl->setAttribute("job_query", $this->_pfQuery);
                    } else {
                        $jobInOptionsEl->setAttribute(
                            "job_query", "select * from " . $this->_pfDatasource->getDbTable()
                        );
                    }
                    $jobInOptionsEl->setAttribute("is_simple_query", "Yes");
                    $jobInOptionsEl->setAttribute("table", $this->_pfDatasource->getDbTable());
                    $jobInOptionsEl->setAttribute("all_records", "Yes");
                } else {
                    $jobInOptionsEl->setAttribute("data_source", "");
                    $jobInOptionsEl->setAttribute("job_query", "");
                    $jobInOptionsEl->setAttribute("is_simple_query", "Yes");
                    $jobInOptionsEl->setAttribute("table", "");
                    $jobInOptionsEl->setAttribute("all_records", "Yes");
                }


                $jobOutOptionsEl = $parent->ownerDocument->createElement("job_out_options");
                $jobEl->appendChild($jobOutOptionsEl);
                $jobOutOptionsEl->setAttribute("output_format", "BMP");
                $jobOutOptionsEl->setAttribute("send_to_type", "Default");
                $jobBitmapEl = $parent->ownerDocument->createElement("job_bitmap");
                $jobOutOptionsEl->appendChild($jobBitmapEl);
                $jobBitmapEl->setAttribute("code", "Jpeg");
                $jobBitmapEl->setAttribute("adjustcode", $this->_pfAdjustcode);
                $jobBitmapEl->setAttribute("size", $this->_pfOutputSize);
                $jobBitmapEl->setAttribute("color_matching", "CMYK");
                if ($this->_pfPageRange != null) {
                    $jobBitmapEl->setAttribute("page_range", $this->_pfPageRange);
                }

                if ($this->_jobName == "Job 1") {
                    $jobRendererEl = $parent->ownerDocument->createElement("job_renderer");
                    $jobOutOptionsEl->appendChild($jobRendererEl);
                    $jobRendererEl->setAttribute("description", "Portable Document Format (PDF)");
                    $jobRendererEl->setAttribute("image_handling", "HiResBinary");
                    $options = "302c302c2c2c322c312c312c302c302c312c322c312c312c32372c322c302c32372c";
                    $options .= "312c302c302c2c302c302c302c302c302c302c2c302c2c302c2c302c302c31362c";
                    $options .= "312c302c302c302c312c302c302c302c30";
                    $jobRendererEl->setAttribute("options", $options);

                }

            } else {
                if ($this->_type == self::PDF) {
                    $jobEl = $parent->ownerDocument->createElement("job");
                    $parent->appendChild($jobEl);
                    $jobEl->setAttribute("name", $this->_jobName);
                    $jobEl->setAttribute("template", $this->_templateName);

                    $jobInOptionsEl = $parent->ownerDocument->createElement("job_in_options");
                    $jobEl->appendChild($jobInOptionsEl);
                    if ($this->_pfDatasource) {
                        $jobInOptionsEl->setAttribute("data_source", $this->_pfDatasource->getName());
                        if ($this->_pfQuery != null && strlen($this->_pfQuery) > 0) {
                            $jobInOptionsEl->setAttribute("job_query", $this->_pfQuery);
                        } else {
                            $jobInOptionsEl->setAttribute(
                                "job_query", "select * from " . $this->_pfDatasource->getDbTable()
                            );
                        }
                        $jobInOptionsEl->setAttribute("is_simple_query", "Yes");
                        $jobInOptionsEl->setAttribute("table", $this->_pfDatasource->getDbTable());
                        $jobInOptionsEl->setAttribute("all_records", "Yes");
                    } else {
                        $jobInOptionsEl->setAttribute("data_source", "");
                        $jobInOptionsEl->setAttribute("job_query", "");
                        $jobInOptionsEl->setAttribute("is_simple_query", "Yes");
                        $jobInOptionsEl->setAttribute("table", "");
                        $jobInOptionsEl->setAttribute("all_records", "Yes");
                    }

                    $jobOutOptionsEl = $parent->ownerDocument->createElement("job_out_options");
                    $jobEl->appendChild($jobOutOptionsEl);
                    $jobOutOptionsEl->setAttribute("output_format", "RENDERER");
                    $jobOutOptionsEl->setAttribute("send_to_type", "Default");

                    $jobRendererEl = $parent->ownerDocument->createElement("job_renderer");
                    $jobOutOptionsEl->appendChild($jobRendererEl);
                    $jobRendererEl->setAttribute("description", "Portable Document Format (PDF)");
                    $jobRendererEl->setAttribute("image_handling", "HiResBinary");
                    $jobRendererEl->setAttribute("single_file_output", "Yes");
                    $options = "302c302c2c2c322c312c312c302c302c312c322c312c312c32372c";
                    $options .= "322c302c32372c312c302c302c2c302c302c302c302c302c302c2c";
                    $options .= "302c2c302c2c302c302c31362c312c302c302c302c312c302c302c302c30";
                    $jobRendererEl->setAttribute("options", $options);

                } else {
                    throw new \Exception(sprintf("Job %s has invalid type %d", $this->_jobName, 
                                                 $this->_type));
                }
            }
        }
    }
}