<?php
namespace App\Core\Utility\Pageflex;

use App\Core\Utility\Pageflex\Object;
use Illuminate\Support\Facades\Log as Logger;
    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     * @version $Id$
     */

/**
 * The Project class.
 *
 * The Project class groups and manages all Pageflex project related artefacts.
 * It allows creating a Project object from scratch or by reading a project file created with Pageflex Studio.
 *
 * Note that not all Pageflex Studio project features are supported.
 * An attempt to load a project file that uses one of the unsupported
 * features will generate an appropriate(?) exception.
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 */

class Project extends Object
{

    // File reference (string) to project file
    private $_pfProjectFile;
    // PFVariable array containing variables defined for this project
    private $_variables;
    // PFDatasource reference with the datasource defined for this project
    private $_dataSource;
    // PFJob array containing the jobs defined for this project
    private $_jobs = array();

    public $tempProjectFile;

    /**
     * Constructor
     *
     * Loads the information from a Pageflex project file.
     *
     * When null is passed an empty PFProject object will created.
     * @param string $projectFile - Path to the project's .pf file or null
     * @throws \Exception
     */
    public function __construct($projectFile = null)
    {
        $this->_pfProjectFile = $projectFile;
        if ($projectFile != null) {
            // try up to 5 times to load project
            for ($i = 0; $i < 5; $i++) {
                $this->loadProjectFile($projectFile);
                if (count($this->_jobs) != 0) {
                    break;
                }
                sleep(1);
            }
        }
    }

    /**
     * Return project file property associated with this PFProject object
     *
     * Normally this is the file from which the project was loaded during construction {@link __construct()}
     * or the file to which the project was saved {@link save()}.
     *
     * @return string project file associated with this PFProject object
     *
     */
    public function getProjectFile()
    {
        return $this->_pfProjectFile;
    }

    /**
     * Sets projectFile property associated with this PFProject object.
     *
     * This method is typically not very usefull as the projectFile property is also set when
     * a PFProject object is loaded from a file {@link __construct()} or saved to a file {@link save()}/
     *
     * @param string $pfProjectFile Value for project file property.
     *
     * @ignore
     *
     */
    public function setProjectFile($pfProjectFile)
    {
        $this->_pfProjectFile = $pfProjectFile;
    }

    /**
     * Returns DataSource object associated with this project
     *
     * @return DataSource Datasource associated with this project.
     *
     */
    public function getDataSource()
    {
        return $this->_dataSource;
    }

    /**
     * Set PFDatasourse associated with this project
     *
     * @param DataSource $dataSource PFDatasource object to associate with this PFProject object.
     *
     */
    public function setDataSource($dataSource)
    {
        $this->_dataSource = $dataSource;
    }

    /**
     * Returns array of PFJob (Pageflex jobs) associated with this PFProject object
     *
     * @return array() array with PFJob objects associated with this project.
     *
     */
    public function getJobs()
    {
        return $this->_jobs;
    }

    /**
     * Sets array of PFJob objects (Pageflex jobs) associated with this project.
     *
     * @param array() PFJob Array of PFJob's to associate with this project
     *
     */
    public function setJobs($jobs)
    {
        $this->_jobs = $jobs;
    }

    /**
     * Returns array of PFVariable objects associated with this project.
     *
     * @return array() PFVariable array associated with this project.
     *
     */
    public function getVariables()
    {
        return $this->_variables;
    }

    /**
     * Sets array of PFVariable objects associated with this project.
     *
     * @param array() PFVariable array to associate with this project.
     *
     */
    public function setVariables($variables)
    {
        $this->_variables = $variables;
    }


    /**
     * Load the project information from the give file
     *
     * @param string $pfFile File to load project information from.
     * @throws \Exception
     * @internal
     */
    private function loadProjectFile($pfFile)
    {
        $projectDomDoc = new \DOMDocument("1.0", "ISO-10646-UCS-2");
        $contents = file_get_contents($pfFile);
        $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-10646-UCS-2');
        $contents = str_replace('ISO-10646-UCS-2', 'UTF-8', $contents);
        @$projectDomDoc->loadHTML($contents);
        // Datasource
        $this->_dataSource = null;
        $dataSourceElementNL = $projectDomDoc->getElementsByTagName("data_source");
        if ($dataSourceElementNL->length > 1) {
            throw new \Exception
            (
                "[PFProject:loadProjectFile]Project files with more than one" .
                " data_source element are not supported"
            );
        } else {
            if ($dataSourceElementNL->length == 1) {
                $this->_dataSource = new DataSource();
                $this->_dataSource->fromdom($dataSourceElementNL->item(0));
            }
        }

        // Variables
        $this->_variables = array();
        $varNodeList = $projectDomDoc->getElementsByTagName("var");
        for ($i = 0; $i < $varNodeList->length; $i++) {
            $varNode = $varNodeList->item($i);
            $varName = $varNode->getAttribute("name");
            $var = new Variable($varName);
            $var->fromDom($varNode);
            $this->_variables[$var->getName()] = $var;
        }

        // PageFlex Jobs
        $jobNodeList = $projectDomDoc->getElementsByTagName("job");
        for ($i = 0; $i < $jobNodeList->length; $i++) {
            $job = new Job();
            $jobNode = $jobNodeList->item($i);
            $job->fromDom($jobNode);
            $this->_jobs[$job->getName()] = $job;
        }

    }

    /**
     * Save project information to given file
     *
     * @param string $pfFile File to save project information to.
     * @throws \Exception
     */
    public function save($pfFile)
    {

        $pfDomDoc = new \DOMDocument("1.0");
        $pfDomDoc->formatOutput = true;

        /* <? pf_project_file version='1.1' ?> */
        $pfDomDoc->appendChild($pfDomDoc->createProcessingInstruction("pf_project_file", "version='1.1'"));

        /* <project_file  xmlns:pfproject="http://www.pageflexinc.com/schemas/projectfile" > */
        $projectFileEl = $pfDomDoc->createElement("project_file");
        $pfDomDoc->appendChild($projectFileEl);
//        $xmlsAttr = $projectFileEl->setAttribute(
//            "xmlns:pfproject", "http://www.pageflexinc.com/schemas/projectfile"
//        );
//        $projectFileEl->setAttributeNodeNS($xmlsAttr);

        // <app_vars app_version="150" app_build="4.6.1.590.20" script_version="210" />
        $appVarsEl = $pfDomDoc->createElement("app_vars", null);
        $projectFileEl->appendChild($appVarsEl);
        $appVarsEl->setAttribute("app_version", "150");
        $appVarsEl->setAttribute("app_build", "4.6.1.590.20");
        $appVarsEl->setAttribute("script_version", "210");

        // Data source
        if ($this->_dataSource != null) {
            $this->_dataSource->toDom($projectFileEl);
        }

        // Variables
        /* <var_collection> */
        $varCollectionEl = $pfDomDoc->createElement("var_collection");
        $projectFileEl->appendChild($varCollectionEl);
        foreach ($this->_variables as $variable) {
            $variable->toDom($varCollectionEl);
        }


        // Jobs
        /* <job_collection default_job_name=""> */
        $jobCollectionEl = $pfDomDoc->createElement("job_collection");
        $projectFileEl->appendChild($jobCollectionEl);
        $jobCollectionEl->setAttribute("default_job_name", "");
        if (!is_array($this->_jobs)) {
            Logger::debug(
                print_r(
                    array(
                        'PFProjectDebug'   => $this,
                        '$_pfProjectFile'  => mb_convert_encoding(
                            @file_get_contents($this->_pfProjectFile), 'UTF-8', 'ISO-10646-UCS-2'
                        ),
                        '$tempProjectFile' => mb_convert_encoding(
                            @file_get_contents($this->tempProjectFile), 'UTF-8', 'ISO-10646-UCS-2'
                        ),
                        '$_SESSION'        => $_SESSION,
                        'backtrace'        => debug_backtrace()
                    ), true
                )
            );
        }
        foreach ($this->_jobs as $job) {
            $job->toDom($jobCollectionEl);
        }

        // Write it out to the file. Unfortunately this is messy stuff.
        // In theory I think the XMLDocument->save would do the job
        // but apparently the resulting XML file is not recognized by
        // Pageflex ( I am guessing there
        // is something going on with the encoding).
        //
        // Ttere is also an issue with Pageflex 4.5 which fails to deal with things like category="&lt;var&gt;".
        // It wants the &gt; replaced with >. Note that Pageflex 4.6 is ok with the &gt;
        //
        // So,... first write XML out to a string with the "default encoding".
        // Then, replace the &gt; where needed. Then, eat the <?xml... PI and replace it.
        // Finally encode everything using mb_convert_encoding and write it to the .pf file
        // ...and hope for the best...

        $xmlEncoding = "<?xml version='1.0' encoding='UCS-2' ?>";
        $xmlStr = $pfDomDoc->saveXML();

        // Make Pageflex 4.5 happy (replace category="&lt;variable&gt;" with category="&lt;variable>;"
        $xmlStr = preg_replace('/category="&lt;([^&]*)&gt;"/', 'category="&lt;$1>"', $xmlStr);
        // Go after the PI - needs to be replaced with one that has the correct encoding
        $xmlStr2 = substr($xmlStr, strlen("<?xml version='1.0'?>"));

        $fh = fopen($pfFile, "w");
        if (!$fh) {
            throw new \Exception(sprintf("Failed to open %s", $pfFile));
        }
        //
        if (!fwrite($fh, sprintf("%s%s", pack("C", 0xff), pack("C", 0xfe)))) {
            throw new \Exception(sprintf("Failed to write to %s", $pfFile));
        }
        if (!fwrite($fh, mb_convert_encoding($xmlEncoding, "UCS-2LE"))) {
            throw new \Exception(sprintf("Failed to write to %s", $pfFile));
        }
        if (!fwrite($fh, mb_convert_encoding($xmlStr2, "UCS-2LE"))) {
            throw new \Exception(sprintf("Failed to write to %s", $pfFile));
        }
        /*if (!fwrite($fh, mb_convert_encoding($xmlStr2, "UCS-2LE"))) {
            throw new Exception(sprintf("Failed to write to %s", $pfFile));
        }*/
        fclose($fh);
        // Set project file property
        $this->_pfProjectFile = $pfFile;
    }


    /**
     * Sync values of fixed variables with the values in the given (.EDIT) xvp file
     *
     * @param string $xvpFile Path to the xvp file
     * @since 1.6
     */
    public function syncVarsWithXvp($xvpFile)
    {
        // Load xvp file
        $xvpDoc = new \DOMDocument("1.0");
        $xvpDoc->load($xvpFile);
        if ($xvpDoc != null) {
            $varElNl = $xvpDoc->getElementsByTagNameNS(
                "http://www.pageflexinc.com/schemas/2003/pfjob", "var"
            );
            for ($ix = 0; $ix < $varElNl->length; $ix++) {
                $xvpVarName = $varElNl->item($ix)->getAttribute("name");
                $pfVar = $this->_variables[$xvpVarName];
                if ($pfVar && $pfVar->isFixed()) {
                    $pfVar->setValue($varElNl->item($ix)->nodeValue);
                }
            }
        }
    }
    
}