<?php
namespace App\Core\Workflow;

use App\Core\Utility\Pageflex\Job;
use App\Core\Utility\Pageflex\MPServer;
use App\Core\Utility\Pageflex\Project;
use App\Core\Utility\Pageflex\Utility;
use Carbon\Carbon;

/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/24/16
 * Time: 2:41 PM
 */
class PageflexRenderer
{

    public $userProject;

    /**
     * @var string md5 hash of the template.pf
     */
    protected $_templatePfHash;

    /**
     * @var string md5 hash of the template.xdt
     */
    protected $_templateXdtHash;

    private $_pfProject;

    /**
     * set project
     * @param $userProject
     * @throws \Exception
     */
    public function __construct($userProject)
    {
        $this->setProject($userProject);
    }

    /**
     * set project
     * @param \App\Core\Models\EZT2\User\Project $userProject
     * @throws \Exception
     */
    public function setProject(\App\Core\Models\EZT2\User\Project $userProject)
    {
        $this->userProject = $userProject;
        $this->_pfProject = new Project($this->userProject->file_path);
    }

    /**
     * get project
     *
     * @return project
     */
    public function getProject()
    {
        return $this->_pfProject;
    }

    /**
     * get variables
     *
     * @return array
     */
    public function getVariables()
    {
        $pfVariables = $this->_pfProject->getVariables();
        $vars = array();
        foreach ($pfVariables as $pfVariable) {
            $varName = $pfVariable->getName();
            $vars[$varName] = $pfVariable->getValue();
        }
        return $vars;
    }

    private function variableSort($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    /**
     * get variables with the associated meta information necessary to determine
     * how and whether to display variables.
     *
     * @return array
     */

    public function getVariablesWMeta()
    {
        $variables = array();
        $frontVariables = array();
        $backVariables = array();
        $projectVariables = $this->_pfProject->getVariables();
        $pages = $this->userProject->designs;
        $displayFrontVarCount = 0;
        $displayBackVarCount = 0;
        if (isset($pages[1])) {
            // front variables
            foreach ($pages[1]->customizableDesignVariables as $frontVariable) {
                if ($frontVariable->allowUserEdit == 1) {
                    $displayFrontVarCount++;
                }
                $frontVariables[$frontVariable->variable->name]->id = $frontVariable->variable->id;
                $frontVariables[$frontVariable->variable->name]->value =
                    $projectVariables[$frontVariable->variable->name]->getValue();
                $frontVariables[$frontVariable->variable->name]->allowUserEdit = $frontVariable->allowUserEdit;
                $frontVariables[$frontVariable->variable->name]->order = $frontVariable->order;
                $frontVariables[$frontVariable->variable->name]->type = $frontVariable->variable->type;
                $frontVariables[$frontVariable->variable->name]->name = $frontVariable->variable->name;
                $frontVariables[$frontVariable->variable->name]->label = $frontVariable->variable->label;
                $frontVariables[$frontVariable->variable->name]->pfkey = $frontVariable->variable->pfkey;
            }
            uasort($frontVariables, array($this, 'variableSort'));
        }
        if (isset($pages[2])) {
            // back variables
            foreach ($pages[2]->customizableDesignVariables as $backVariable) {

                if ($backVariable->allowUserEdit == 1) {
                    $displayBackVarCount++;
                }
                $backVariables[$backVariable->variable->name]->id = $backVariable->variable->id;
                $backVariables[$backVariable->variable->name]->value =
                    $projectVariables[$backVariable->variable->name]->getValue();
                $backVariables[$backVariable->variable->name]->allowUserEdit = $backVariable->allowUserEdit;
                $backVariables[$backVariable->variable->name]->order = $backVariable->order;
                $backVariables[$backVariable->variable->name]->type = $backVariable->variable->type;
                $backVariables[$backVariable->variable->name]->name = $backVariable->variable->name;
                $backVariables[$backVariable->variable->name]->label = $backVariable->variable->label;
                $backVariables[$backVariable->variable->name]->pfkey = $backVariable->variable->pfkey;
            }
            uasort($backVariables, array($this, 'variableSort'));
        }
        if (count($backVariables) > 0 && count($frontVariables) > 0) {
            $variables['common'] = array_intersect_key($frontVariables, $backVariables);
            $variables['front'] = array_diff_key($frontVariables, $variables['common']);
            $variables['back'] = array_diff_key($backVariables, $variables['common']);
            $variables['displayBackVarCount'] = $displayBackVarCount;
            $variables['displayFrontVarCount'] = $displayFrontVarCount;
        } elseif (count($backVariables) > 0) {
            // no front variables
            $variables['back'] = $backVariables;
            $variables['displayBackVarCount'] = $displayBackVarCount;
        } elseif (count($frontVariables) > 0) {
            // no back variables
            $variables['displayFrontVarCount'] = $displayFrontVarCount;
            $variables['front'] = $frontVariables;
        }
        return $variables;
    }

    private function _prepareMPServer(Array $jobs)
    {
        $designServerConfig = config('app.server_config.designServer');
        $server = $designServerConfig['server'];
        $port = $designServerConfig['port'];
        $connectionTimeout = $designServerConfig['timeout'];
        foreach ($jobs as $job){
            $this->getPfJob($job);
        }
        return new MPServer($server, $port, $connectionTimeout);
    }


    private function _callMPServer(MPServer $server, Array $jobs, $job)
    {
        //grab file paths
        $windowsPath = str_replace('template.pf', '', $this->userProject->file_path_windows);
        $linuxPath = pathinfo($this->userProject->file_path, PATHINFO_DIRNAME);
        $tempFile = pathinfo(tempnam($linuxPath, 'temp_pf_'), PATHINFO_BASENAME);
        $this->_pfProject->tempProjectFile = $windowsPath . $tempFile;
        $this->_pfProject->save($linuxPath . '/' . $tempFile);
        return $server->submit(null, $this->_pfProject, $jobs[$job]);
    }

    /**
     * Returns the front and back image preview in JPG
     * @return array
     */
    public function getJpgPreview()
    {
        $designServerConfig = config('app.server_config.designServer');
        //get the number of pages from the project
        $numPages = $this->userProject->customizableDesigns()->count();
        $mpServer = $this->_prepareMPServer(['JPGpreview', 'smallThumbnail', 'largeThumbnail']);
        $pfJobs = $this->_pfProject->getJobs();
        $rootPath = $designServerConfig['temporaryOutput'] . '/mpowertmp';
        try {
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'JPGpreview');
            if ($numPages > 1) {
                $frontImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_2.jpg";
            } else {
                $frontImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
            }
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'smallThumbnail');
            if ($numPages > 1) {
                $frontSmallThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backSmallThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_2.jpg";
            } else {
                $frontSmallThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backSmallThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
            }
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'largeThumbnail');
            $this->_writeFinalProjectFile($this->_pfProject->getProjectFile());
            if ($numPages > 1) {
                $frontLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_2.jpg";
            } else {
                $frontLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
            }
        } catch (\Exception $e) {
            $errorLog = new \App\Core\Models\OrderCore\Log();
            $errorLog->source = 'PageFlexPreview';
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage() . ": " . $e->getTraceAsString();
            $errorLog->save();
            return false;
        }

        $jpgPreview = array('preview'    => array(
            'front' => $frontImagePath, 'back' => $backImagePath),
                            'thumbnails' => array(
                                'small' => array('front' => $frontSmallThumbFilePath, 'back' => $backSmallThumbFilePath),
                                'large' => array('front' => $frontLargeThumbFilePath, 'back' => $backLargeThumbFilePath)));

        return $jpgPreview;
    }

    public function getSignupPreview()
    {
        //get the number of pages from the project
        $numPages = $this->userProject->customizableDesigns()->count();
        $designServerConfig = config('app.server_config.designServer');
        $mpServer = $this->_prepareMPServer(['largeThumbnail']);
        $pfJobs = $this->_pfProject->getJobs();
        try {
            $rootPath = $designServerConfig['temporaryOutput'] . '/mpowertmp';
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'largeThumbnail');
            $this->_writeFinalProjectFile($this->_pfProject->getProjectFile());
            if ($numPages > 1) {
                $frontLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_2.jpg";
            } else {
                $frontLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backLargeThumbFilePath = $rootPath . "/" .
                    $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
            }
        } catch (\Exception $e) {
            $errorLog = new \App\Core\Models\OrderCore\Log();
            $errorLog->source = 'PageFlexPreview';
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage() . ": " . $e->getTraceAsString();
            $errorLog->save();

            return false;
        }

        $jpgPreview = array('thumbnails' => array(
                                'large' => array('front' => $frontLargeThumbFilePath, 'back' => $backLargeThumbFilePath)));
        return $jpgPreview;
    }


    public function getLargeJpgPreview()
    {
        //get the number of pages from the project
        $numPages = $this->userProject->customizableDesigns()->count();
        $mpServer = $this->_prepareMPServer(['JPGpreview']);
        $pfJobs = $this->_pfProject->getJobs();
        try {
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'JPGpreview');
            $this->_writeFinalProjectFile($this->_pfProject->getProjectFile());
            if ($numPages > 1) {
                $frontImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_2.jpg";
            } else {
                $frontImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
                $backImagePath = $mpJob->getMpJobId() . "/" .
                    $mpJob->getMpJobId() . "_00001_1.jpg";
            }
        } catch (\Exception $e) {
            $errorLog = new \App\Core\Models\OrderCore\Log();
            $errorLog->source = 'PageFlexPreview';
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage() . ": " . $e->getTraceAsString();
            $errorLog->save();

            return false;
        }

        $cacheBustString = '?' . time();

        return array(
            'preview' => array(
                'front' => "{$frontImagePath}{$cacheBustString}",
                'back' => "{$backImagePath}{$cacheBustString}"
            )
        );
    }

    /**
     * Returns the front and back in PDF
     *
     * @param bool $copyMpowerTmp Whether to copy generated PDF to project folder
     * @return array
     */
    public function getPdfPreview($copyMpowerTmp = true)
    {
        $designServerConfig = config('app.server_config.designServer');
        $mpServer = $this->_prepareMPServer(['PDFpreview']);
        $pfJobs = $this->_pfProject->getJobs();
        try {
            $mpJob = $this->_callMPServer($mpServer, $pfJobs, 'PDFpreview');
            $this->_writeFinalProjectFile($this->_pfProject->getProjectFile());

        } catch (\Exception $e) {
            $errorLog = new \App\Core\Models\OrderCore\Log();
            $errorLog->source = 'PageFlexPreview';
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage() . ": " . $e->getTraceAsString();
            $errorLog->save();
        }
        $jobPath = $mpJob->getMpJobId() . "/" . $mpJob->getMpJobId() . "_00001.pdf";
        $rootPath = $designServerConfig['temporaryOutput'] . '/mpowertmp';
        $pdfPath = array('previewPath' => $jobPath,
                         'filePath'    => $rootPath . '/' . $jobPath);
        if ($copyMpowerTmp) {
            $filePath = dirname($this->_pfProject->getProjectFile()) . '/template.pdf';
            copy($pdfPath['filePath'], $filePath);
            $pdfPath['previewPath'] = str_replace($designServerConfig['temporaryOutput'] . '/', '', $filePath);
            $pdfPath['filePath'] = $filePath;
        }

        return $pdfPath;
    }

    private function _writeFinalProjectFile($tempFile)
    {
        $fp = fopen($this->userProject->file_path, "w");
        if (flock($fp, LOCK_EX)) {
            $tempFileContents = file_get_contents($tempFile);
            $this->_templatePfHash = md5($tempFileContents);
            fwrite($fp, $tempFileContents);
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            usleep(250000);
            $this->_writeFinalProjectFile($tempFile);
        }
        $tf = fopen($tempFile, "w");
        if (flock($tf, LOCK_EX)) {
            flock($tf, LOCK_UN);
            fclose($tf);
            unlink($tempFile);
        }
    }

    /**
     * Saves updated fields to project file
     *
     * @param array post parameters
     * @return void
     */
    public function updatePostData($params)
    {
        if (isset($params['projectName']) && $params['projectName'] != '') {
            $this->userProject->name = $params['projectName'];
            $this->userProject->save();
        }

        $pfVariables = $this->_pfProject->getVariables();
        foreach ($pfVariables as $pfVariable) {
            $pfVariableName = $pfVariable->getName();

            if (array_key_exists($pfVariableName, $params)) {
                $pfVariableValue = $params[$pfVariableName];
                $pfVariable->setValue($pfVariableValue);
            }
        }

        $this->_pfProject->setVariables($pfVariables);
        $this->_pfProject->save($this->userProject->file_path);
        Utility::savingProjectFile($this->userProject);
    }

    public function getTemplateHash()
    {
        if (empty($this->_templatePfHash)) {
            $this->_templatePfHash = md5(
                file_get_contents($this->userProject->file_path)
            );
        }
        if (empty($this->_templateXdtHash)) {
            $this->_templateXdtHash = md5(
                file_get_contents(str_replace('template.pf', 'template.xdt', $this->userProject->file_path))
            );
        }
        return md5($this->_templatePfHash . $this->_templateXdtHash);
    }


    /*
     * check the pfProject jobs collection for the name, if not there, add it
     * this is necessary because the project files created under the old
     * expresscopy.com can not be depended on to have the necessary jobs
     * as part of the project file's job collection
     */
    private function getPfJob($jobName)
    {
        $currentJobs = $this->_pfProject->getJobs();
        $designs = $this->userProject->getDesigns();
        if (!array_key_exists($jobName, $currentJobs)) {
            if ($jobName == 'JPGpreview') {
                $pfJob = new Job($jobName, 'template', null, 2);
                $pfJob->setAdjustCode('Percent');
                switch ($designs['1']->size_display) {
                    case 'Reg Postcard':
                        $pfJob->setSize('85');
                        break;
                    case 'Jumbo Postcard':
                        $pfJob->setSize('63');
                        break;
                    case 'Std Flyer':
                        $pfJob->setSize('58');
                        break;
                    case 'Lgl Flyer':
                        $pfJob->setSize('63');
                        break;
                    case 'Tab Flyer':
                        $pfJob->setSize('60');
                        break;
                    case 'Bus Card':
                        $pfJob->setSize('120');
                        break;
                    case 'Pan Postcard':
                        $pfJob->setSize('47');
                        break;
                    case 'Calendar':
                        $pfJob->setSize('63');
                        break;
                    default:
                        $pfJob->setSize('65');
                        break;
                }
                $pfJob->setPFJpgQuality('40');
                $currentJobs[$jobName] = $pfJob;
            } else {
                if ($jobName == 'PDFpreview') {
                    $pfJob = new Job($jobName, 'template', null, 3);
                    $currentJobs[$jobName] = $pfJob;
                } else {
                    if ($jobName == 'smallThumbnail') {
                        $pfJob = new Job($jobName, 'template', null, 2);
                        if ($designs[1]->orientation == 1) {
                            $pfJob->setAdjustCode('Width');
                        } else {
                            $pfJob->setAdjustCode('Height');
                        }

                        switch ($designs[1]->size_display) {
                            case 'Reg Postcard':
                                $pfJob->setSize('114');
                                break;
                            case 'Jumbo Postcard':
                                $pfJob->setSize('140');
                                break;
                            case 'Std Flyer':
                                $pfJob->setSize('112');
                                break;
                            case 'Lgl Flyer':
                                $pfJob->setSize('63');
                                break;
                            case 'Tab Flyer':
                                $pfJob->setSize('60');
                                break;
                            case 'Bus Card':
                                $pfJob->setSize('150');
                                break;
                            case 'Pan Postcard':
                                $pfJob->setSize('175');
                                break;
                            case 'Door Hanger':
                                $pfJob->setSize('172');
                                break;
                            case 'Calendar':
                                $pfJob->setSize('140');
                                break;
                            default:
                                $pfJob->setSize('65');
                                break;
                        }
                        $currentJobs[$jobName] = $pfJob;
                    } else {
                        if ($jobName == 'largeThumbnail') {
                            $pfJob = new Job($jobName, 'template', null, 2);
                            if ($designs[1]->orientation == 1) {
                                $pfJob->setAdjustCode('Width');
                            } else {
                                $pfJob->setAdjustCode('Height');
                            }

                            switch ($designs[1]->size_display) {
                                case 'Reg Postcard':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Jumbo Postcard':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Std Flyer':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Lgl Flyer':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Tab Flyer':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Bus Card':
                                    $pfJob->setSize('350');
                                    break;
                                case 'Pan Postcard':
                                    $pfJob->setSize('350');
                                    break;
                                default:
                                    $pfJob->setSize('350');
                                    break;
                            }
                            $currentJobs[$jobName] = $pfJob;
                        }
                    }
                }
            }
        }
        $this->_pfProject->setJobs($currentJobs);
        $this->_pfProject->save($this->userProject->file_path);

    }
}