<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 5/6/16
 * Time: 11:22 AM
 */

namespace App\Core\Utility\Pageflex;
    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     * @version $Id$
     */

/**
 * An MPJob object manages the properties of an Mpower job.
 *
 * The main usage of an MPObject instance is to return
 * information from an invocation of {@link MPServer::submit()} which
 * submits a job to the Mpower server and waits until the Mpower server returns the result.
 *
 * An MPJob object can also be used to set Mpower job variables
 * (as defined in Mpower Server Setup and Reference Guide) before
 * submitting the job to the Mpower server. See {@link setSysJobVar()}
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 *
 */

class MPJob
{

    /**
     * Line separator - Windows only (for now?)
     * @ignore
     */
    const eol = "\r\n";

    // Array of Mpower job variables
    private $_sysJobVars;
    // The job identification returned by the Mpower server
    // after job submission. It is set in the MPServer::submit() method
    private $_mpJobId;
    // Number of documents rendered after submitting this job
    // and as reported by the Mpower server. It is set in the MPServer::submit() method
    private $_documentCount;
    // Number of pages rendered after submitting this job
    // and as reported by the Mpower server. It is set in the MPServer::submit() method
    private $_pageCount;


    /**
     * Constructs and initializes an MPJob object
     */
    public function __construct()
    {
        $this->_sysJobVars = array();
        $this->_sysJobVars["_sys_ClientJobMonitor"] = "TRUE";
        $this->_sysJobVars["_sys_DownloadFonts"] = "TRUE";
    }

    /**
     * Set an Mpower system job variable.
     *
     * E.g. To limit the number of document instances one can use the _sys_JobSQLQuery system variable.
     * <pre>$myMpowerObject->setSysJobVar(
     *     "_sys_JobSQLQuery",
     *     "select * from datafile where name='LINDA G ADAMS'");
     * </pre>
     *
     * @param string $varName The name of the system variable to set.
     * @param string $varValue The value to set the system variable to.
     */
    public function setSysJobVar($varName, $varValue)
    {
        $this->_sysJobVars[$varName] = $varValue;
    }


    /**
     * Return the job id which was assigned to this job after submission by the Mpower server.
     * @return string The job id for this job assigned to the Mpower server.
     */
    public function getMpJobId()
    {
        return $this->_mpJobId;
    }

    /**
     * Sets the job id as returned from the Mpower server. This method is (typically) only called from
     * within MPServer::submit() and not directly by the user application.
     *
     * @param string $mpJobId The job id returned from the Mpower server.
     *
     * @ignore
     */
    public function setMpJobId($mpJobId)
    {
        $this->_mpJobId = $mpJobId;
    }

    /**
     * Returns the number of documents rendered the last time this job was submitted.
     *
     * @return int The number of rendered documents.
     */
    public function getDocumentCount()
    {
        return $this->_documentCount;
    }

    /**
     * Sets the document count property of this MPJob object. This method is (typically) only called from
     * within MPServer::submit() and not directly by the user application.
     *
     * @param int $documentCount New value for document count property.
     *
     * @ignore
     *
     */
    public function setDocumentCount($documentCount)
    {
        $this->_documentCount = $documentCount;
    }


    /**
     * Returns the number of pages rendered the last time this job was submitted.
     *
     * @return int The number of rendered pages.
     */
    public function getPageCount()
    {
        return $this->_pageCount;
    }

    /**
     * Sets the page count property of this MPJob object. This method is (typically) only called from
     * within MPServer::submit() and not directly by the user application.
     *
     * @param int $pageCount New value for page count property.
     *
     * @ignore
     *
     */
    public function setPageCount($pageCount)
    {
        $this->_pageCount = $pageCount;
    }

    /**
     * Build XML that reflects this MPJob's settings and that can be submitted to the Mpower server using the Mpower
     * SubmitJob command.
     *
     * The structure of this XML is guided by what is in the Mpower Server Setup and Reference guide.
     *
     * @param Project $pfProject Pageflex project object for which the job has to be build
     * @param Job $pfJob Pageflex job object for which the job has to be build.
     *
     * @ignore
     * @return string
     */
    public function buildMPServerJob($pfProject, $pfJob)
    {
        // Pageflex namespace uri for job XML files
        $pfJobUri = "http://www.pageflexinc.com/schemas";
        // Create DOM document
        //   Note: Although Pageflex claims it wants an UCS-2 XML file, don't create the DOM with such an encoding
        //        (Pageflex fireworks guaranteed if you do)
        $pfJobDoc = new \DOMDocument("1.0");
        // Add pf_command processing instruction.
        /* <?pf_command version="1.0" encoding="UCS-2"?> */
        $pfJobDoc->appendChild($pfJobDoc->createProcessingInstruction("pf_command", "encoding='UCS-2'"));
        // The job_command element
        /* <pfjob:job_command xmlns:pfjob="http://www.pageflexinc.com/schemas" name="SubmitJob"> */
        $jobCommandEl = $pfJobDoc->createElementNs($pfJobUri, "pfjob:job_command");
        $jobCommandEl->setAttribute("name", "SubmitJob");
        $pfJobDoc->appendChild($jobCommandEl);
        // The job_variables element
        /* <pfjob:job_variables> */
        $jobVariablesEl = $pfJobDoc->createElementNS($pfJobUri, "pfjob:job_variables");
        $jobCommandEl->appendChild($jobVariablesEl);
        // First variable - ProjectName (refers to Pageflex .pf file)
        /* <pfjob:var name='ProjectName>...</pfjob:var> */
        $varEl = $pfJobDoc->createElementNS($pfJobUri, "pfjob:var");
        $jobVariablesEl->appendChild($varEl);
        $varEl->setAttribute("name", "ProjectName");
        $varEl->nodeValue = $pfProject->tempProjectFile;
        // Second variable - JobName has to refer to a Pageflex project file
        /* <pfjob:var name='JobName'>...</pfjob:var> */
        $varEl = $pfJobDoc->createElementNS($pfJobUri, "pfjob:var");
        $jobVariablesEl->appendChild($varEl);
        $varEl->setAttribute("name", "JobName");
        $varEl->nodeValue = $pfJob->getName();
        // Pageflex system job variables provided by "user"
        foreach ($this->_sysJobVars as $sjvName => $sjvVal) {
            /* <pfjob:var name='...'>...</pfjob:var> */
            $varEl = $pfJobDoc->createElementNS($pfJobUri, "pfjob:var");
            $jobVariablesEl->appendChild($varEl);
            $varEl->setAttribute("name", $sjvName);
            $varEl->nodeValue = $sjvVal;
        }

        // Serialize XML and return
        return $pfJobDoc->saveXML();

    }


}