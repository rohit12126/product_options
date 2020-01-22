<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/28/16
 * Time: 2:44 PM
 */

namespace App\Core\Workflow;

use App\Core\Models\EZT2\CustomizableDesign;
use App\Core\Models\EZT2\Design\Customizable\Design;
use App\Core\Models\EZT2\PhoneType;
use App\Core\Models\EZT2\States;
use App\Core\Models\EZT2\User\Image;
use App\Core\Models\EZT2\User\Project;
use App\Core\Models\OrderCore\DesignFile;
use App\Core\Models\OrderCore\Invoice\Item;
use App\Core\Models\OrderCore\JobQueue;
use App\Core\Models\OrderCore\Listing;
use App\Core\Models\OrderCore\User;
use App\Core\Utility\FilePath;
use App\Core\Utility\Ghostscript;
use App\Core\Utility\Pageflex\Handoff;
use App\Core\Utility\Pageflex\Style;
use App\Core\Utility\Pageflex\Template;
use Exception;
use Illuminate\Support\Facades\DB;
use mikehaertl\pdftk\Pdf;
use Session;
use stdClass;

class DesignManager
{
    /**
     * @var $_designs array of design information / array of design objects.
     */
    private $_designs = array();

    /**
     * @var $_customizableDesigns array that stores the current customizable designs.
     */
    private $_customizableDesigns = array();

    /**
     * @var $_pdfPreview array of information about the PDF preview, used later on when accepting the PDF
     * as a default.
     */
    private $_pdfPreview;

    /**
     * @var $_renderer PageflexRenderer.
     */
    private $_renderer;

    /**
     * @var $_user User.
     */
    private $_user;

    /**
     * @var array
     */
    private $_pages = array();

    /**
     * @var $_userProject Project.
     */
    private $_userProject;

    const PRODUCT_TYPE_MISMATCH_MSG = "Mismatch sides detected. Your front and back needs to be of the same product "
    . "type. Please correct your selections.";

    /**
     * __construct
     *
     * @param  array $designs
     * @param  \App\Core\Models\OrderCore\User|null $user
     *
     * @throws \Exception
     * @access public
     */
    public function __construct($designs = null, $user = null)
    {
        if (!is_null($user)) {
            $this->_user = $user;
        } else {
            $this->_user = auth()->user();
        }
        if (is_array($designs)) {
            $this->_init($designs);
        }
        return $this;
    }

    /**
     * _init
     *
     * @param mixed $designs
     * @return void
     * @throws Exception
     * @access private
     */
    private function _init(array $designs)
    {
        foreach ($designs as $key => $design) {
            $designs[$key] = $design->customizableDesign;
        }
        $this->_designs = $designs;
        $designServerConfig = config('app.server_config.designServer');

        $this->_userProject = new Project();
        $this->_userProject->save();

        $projectPath = FilePath::calculatePath($this->_userProject->id, false);

        if (!file_exists($designServerConfig['userPath'] . DIRECTORY_SEPARATOR . $projectPath)) {
            $fileTest = mkdir($designServerConfig['userPath'] . DIRECTORY_SEPARATOR . $projectPath, 0777, true);
            if (!$fileTest) {
                throw new \Exception('directory not create');//throw error
            }
        }

        $fullProjectPath = $designServerConfig['userPath'] . DIRECTORY_SEPARATOR . $projectPath;

        $this->_userProject->user_id = $this->_user->id;
        $this->_userProject->status = 'hidden';
        $this->_userProject->file_path = $fullProjectPath . DIRECTORY_SEPARATOR . 'template.pf';
        $this->_userProject->file_path_windows = str_replace(
            '/',
            '\\',
            $designServerConfig['windowsUserPath'] . DIRECTORY_SEPARATOR . $projectPath . DIRECTORY_SEPARATOR . 'template.pf'
        );
        $this->_userProject->name = $this->_designs[1]->title;
        $this->_userProject->created = date('Y-m-d H:i:s');
        $this->_userProject->save();

        foreach ($this->_designs as $page => $design) {
            $customizableDesign = Design::find($design->id);
            $projectDesignLink = new Project\CustomizableDesign();
            $projectDesignLink->user_project_id = $this->_userProject->id;
            $projectDesignLink->customizable_design_id = $customizableDesign->id;
            $projectDesignLink->page = $page;
            $projectDesignLink->save();
        }

        //build out xdt
        $templateFile = new Template($this->_designs);
        $fileContents = $templateFile->getPFTemplate();
        $this->writeFilesToDisk($fullProjectPath . DIRECTORY_SEPARATOR, 'template.xdt', $fileContents);

        //build out pf
        $handoff = new Handoff($this->_designs);
        $handoffResults = $handoff->getProjectFile($this->getUserDesignPreferences());
        $project = $handoffResults['projectFile'];
        $project->save($this->_userProject->file_path);

        //build out tsl
        $styleFile = new Style($this->_designs);
        $fileContents = $styleFile->getPFStyle();
        $this->writeFilesToDisk($fullProjectPath . DIRECTORY_SEPARATOR, 'template.tsl', $fileContents);

        $this->_renderer = new PageflexRenderer($this->_userProject);
        //populate the new project with user info

        $config = config('app.server_config');
        if (isset($config['pulse2'])) {
            if (isset($config['pulse2']['defaultPropertyPhoto'])) {
                $defaultPropertyPhoto = $config['pulse2']['defaultPropertyPhoto'];
                $this->_renderer->updatePostData(
                    array_merge($this->getUserDesignPreferences(), ['FrontImage1' => $defaultPropertyPhoto])
                );
            }
        }
    }


    /**
     * load
     *
     * @param mixed $userProjectId
     *
     * @param bool $status
     * @return int user project ID
     * @access public
     */
    public function load($userProjectId, $status = true)
    {
        $this->_userProject = Project::findOrFail($userProjectId);
        if ($status) {
            $this->_userProject->status = 'hidden';
            $this->_userProject->last_modified = date('Y-m-d H:i:s');
            $this->_userProject->save();
        }
        //remove design file rows and set design files is active to 0
        $this->_renderer = new PageflexRenderer($this->_userProject);
        // update the current selected design(s) with the existing user project
        foreach ($this->_userProject->getDesigns() as $key => $design) {
            $this->_customizableDesigns[$key] = $design;
        }

        return $this->_userProject->id;
    }

    /**
     * getUserProject
     *
     * @access public
     * @return object Instance of Excopy_Model_User_Project
     */
    public function getUserProject()
    {
        return $this->_userProject;
    }

    public function variableSort($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

    /**
     * getVariables
     *
     * @access public
     * @return array $variables Array of variables for the current user project broken into front, back and common.
     */
    public function getVariables()
    {
        $designServerConfig = config('app.server_config.designServer');
        $variables = array();
        $frontVariables = array();
        $backVariables = array();
        $projectVariables = $this->_renderer->getVariables();
        $this->_pages = $this->_userProject->getDesigns();

        if (isset($this->_pages[1])) {
            // front variables
            foreach ($this->_pages[1]->getCustomizableDesignVariables() as $frontVariable) {
                if (!isset($frontVariables[$frontVariable->variable->name])) {
                    $frontVariables[$frontVariable->variable->name] = new stdClass();
                }
                $frontVariables[$frontVariable->variable->name]->id = $frontVariable->variable->id;
                $frontVariables[$frontVariable->variable->name]->value =
                    $projectVariables[$frontVariable->variable->name];
                $frontVariables[$frontVariable->variable->name]->allowUserEdit =
                    $frontVariable->AllowUserEdit;
                $frontVariables[$frontVariable->variable->name]->order = $frontVariable->var_order;
                $frontVariables[$frontVariable->variable->name]->type = $frontVariable->variable->type;
                $frontVariables[$frontVariable->variable->name]->name = $frontVariable->variable->name;
                $frontVariables[$frontVariable->variable->name]->label = $frontVariable->variable->label;
                $frontVariables[$frontVariable->variable->name]->pfkey = $frontVariable->variable->pfkey;
                $frontVariables[$frontVariable->variable->name]->hideControls =
                    $frontVariable->hide_controls;
                $frontVariables[$frontVariable->variable->name]->highQualityImage =
                    $frontVariable->high_quality_image;
                if ('Image' == $frontVariable->variable->pfkey) {
                    $frontVariables[$frontVariable->variable->name]->imTypeId =
                        $frontVariable->variable->im_type_id;
                    if (!empty($projectVariables[$frontVariable->variable->name])) {
                        $userImage = Image::where(
                            DB::raw("CONCAT(`filepath`, '\\\', `filename`)"),
                            $projectVariables[$frontVariable->variable->name]
                        )
                            ->whereIn('ezt_user_id', ['0', '1', $this->_user->ezt_id])
                            ->first();
                        if ($userImage) {
                            $frontVariables[$frontVariable->variable->name]->displayImage = $designServerConfig['url'] . $userImage->thumbnail;
                        } else {
                            $frontVariables[$frontVariable->variable->name]->displayImage = '';
                        }
                    } else {
                        $frontVariables[$frontVariable->variable->name]->displayImage = '';
                    }
                }
            }
            uasort($frontVariables, array($this, 'variableSort'));
        }
        if (isset($this->_pages[2])) {
            // back variables
            foreach ($this->_pages[2]->getCustomizableDesignVariables() as $backVariable) {
                if (!isset($backVariables[$backVariable->variable->name])) {
                    $backVariables[$backVariable->variable->name] = new stdClass();
                }
                $backVariables[$backVariable->variable->name]->id = $backVariable->variable->id;
                $backVariables[$backVariable->variable->name]->value = $projectVariables[$backVariable->variable->name];
                $backVariables[$backVariable->variable->name]->allowUserEdit = $backVariable->AllowUserEdit;
                $backVariables[$backVariable->variable->name]->order = $backVariable->var_order;
                $backVariables[$backVariable->variable->name]->type = $backVariable->variable->type;
                $backVariables[$backVariable->variable->name]->name = $backVariable->variable->name;
                $backVariables[$backVariable->variable->name]->label = $backVariable->variable->label;
                $backVariables[$backVariable->variable->name]->pfkey = $backVariable->variable->pfkey;
                $backVariables[$backVariable->variable->name]->hideControls =
                    $backVariable->hide_controls;
                $backVariables[$backVariable->variable->name]->highQualityImage =
                    $backVariable->high_quality_image;
                if ('Image' == $backVariable->variable->pfkey) {
                    $backVariables[$backVariable->variable->name]->imTypeId =
                        $backVariable->variable->im_type_id;
                    if (!empty($projectVariables[$backVariable->variable->name])) {
                        $userImage = Image::where(
                            DB::raw("CONCAT(`filepath`, '\\\', `filename`)"),
                            $projectVariables[$backVariable->variable->name]
                        )
                            ->whereIn('ezt_user_id', ['0', '1', $this->_user->ezt_id])
                            ->first();
                        if ($userImage) {
                            $backVariables[$backVariable->variable->name]->displayImage = $designServerConfig['url'] . $userImage->thumbnail;
                        } else {
                            $backVariables[$backVariable->variable->name]->displayImage = '';
                        }
                    } else {
                        $backVariables[$backVariable->variable->name]->displayImage = '';
                    }
                }
            }
            uasort($backVariables, array($this, 'variableSort'));
        }
        if (count($backVariables) > 0 && count($frontVariables) > 0) {
            $variables['common'] = array_intersect_key($frontVariables, $backVariables);
            $variables['front'] = array_diff_key($frontVariables, $variables['common']);
            $variables['back'] = array_diff_key($backVariables, $variables['common']);
        } elseif (count($backVariables) > 0) {
            // no front variables
            $variables['back'] = $backVariables;
        } elseif (count($frontVariables) > 0) {
            // no back variables
            $variables['front'] = $frontVariables;
        }
        return $variables;
    }

    /**
     * setVariables
     *
     * @param mixed $params
     * @access public
     * @return void
     */
    public function setVariables($params)
    {
        $mappedVariables = $this->_mapVariables($params);

        $this->_renderer->updatePostData($mappedVariables);
    }

    public function getTemplateHash()
    {
        return $this->_renderer->getTemplateHash();
    }

    /**
     * getJpgPreview
     *
     * @access public
     * @return array JPEG preview meta information.
     *
     * 2010-05-17 -- added processing to take thumbnails that are not retrieved
     * from the rendere getJpgPreview method and move them to the user_project
     * directory and add to the data base for user_project_customizable designs
     * so that the unfinished designs display the current state of the design.
     */
    public function getJpgPreview()
    {
        $this->_userProject->last_modified = date('Y-m-d H:i:s');
        $this->_userProject->save();
        $jpgPreview = $this->_renderer->getJpgPreview();
        if (!$jpgPreview) {
            return false;
        }
        $this->saveThumbnails($jpgPreview['thumbnails']);
        return $jpgPreview['preview'];
    }

    public function getLargeJpgPreview()
    {
        $this->_userProject->last_modified = date('Y-m-d H:i:s');
        $this->_userProject->save();
        $jpgPreview = $this->_renderer->getLargeJpgPreview();
        if (!$jpgPreview) {
            return false;
        }
        return $jpgPreview['preview'];
    }

    public function getSignupPreview()
    {
        $this->_userProject->last_modified = date('Y-m-d H:i:s');
        $this->_userProject->save();
        $signupPreview = $this->_renderer->getSignupPreview();
        if (!$signupPreview) {
            return false;
        }
        $this->saveThumbnails($signupPreview['thumbnails']);
        foreach ($this->_userProject->customizableDesigns()->get() as $design) {
            $previews[($design->page == 1 ? 'front' : 'back')]['thumbnail'] =
                $design->large_thumb . '?' . time();
            $previews['projectId'] = $this->_userProject->id;
        }

        return $previews;
    }


    /**
     * getPdfPreview
     *
     * @access public
     * @param bool $copyMpowerTmp
     * @return array PDF preview meta information.
     */
    public function getPdfPreview($copyMpowerTmp = true)
    {
        $this->_pdfPreview = $this->_renderer->getPdfPreview($copyMpowerTmp);
        return $this->_pdfPreview;
    }

    /**
     * @param string $filePath
     * @param int $invoiceItemId
     * @return null
     * @throws Exception
     */
    public function accept($filePath = null, $invoiceItemId = null)
    {
        if (!$filePath || !file_exists($filePath)) {
            $this->getPdfPreview();
            $filePath = $this->_pdfPreview['filePath'];
        }
        // Create temporary dir for split PDFs
        $root = $this->_getUserPrintFileRoot();
        $tmpDir = $root . DIRECTORY_SEPARATOR . uniqid('df-tmp-');
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        // Split pdf
        if (!$filePath) {
            throw new Exception("pdfPreview expected, but none given");
        }

        $pageCount = Ghostscript::getPageCount($filePath);

        Ghostscript::splitPdf($filePath, $tmpDir, 1, $pageCount);
        $splitFiles = array();
        for ($i = 0; $i < $pageCount; $i++) {
            $splitFiles[$i + 1] = new \App\Core\Utility\File($tmpDir . DIRECTORY_SEPARATOR . ($i + 1) . '.pdf');
        }

        // Get project's customizableDesigns per page.
        $this->_userProject->load('customizableDesigns');
        $customizableDesignsPerPage = $this->_userProject->getDesigns();

        // Delete previous design files that belong to this project
        $designFileLinks = $this->_userProject->getDesignfiles()->get();
        foreach ($designFileLinks as $designFileLink) {
            $design = $designFileLink->designFile;
            $design->is_active = 0; //inactivate old design files for user_project
            $design->save(); //to prevent orphaned design files with no project
            $designFileLink->delete(); //delete relationship for the old designs from the old design_file_user_project
        }

        // design files to return
        $designFiles = null;
        foreach ($customizableDesignsPerPage as $pageNumber => $customizableDesign) {
            // Create and save a design file
            if (count($splitFiles) == 1) {
                $pdfFile = array_shift($splitFiles);
            } else {
                $pdfFile = $splitFiles[$pageNumber];
            }
            $designFile = new DesignFile();
            $designFile->product_print_id = $customizableDesign->product_print_id;
            $designFile->tmpPdf = $pdfFile;
            $designFile->name = $this->_userProject->name;
            $designFile->page = $pageNumber;
            $designFile->user_id = $this->_userProject->user_id;
            $designFile->status = 'approved';
            $designFile->type = 'customized';
            $designFile->save();
            $designFile->generateThumbnails();

            // Get the user project as well, append that to the designFile->userProject table.
            $designFile->addUserProject($this->_userProject);

            // build the return value
            $designFiles[$pageNumber] = $designFile;

            // TODO get rid of this page to side translation -- remove 'front' and 'back' entirely?
            $side = ($pageNumber == 1) ? 'front' : 'back';
            $this->_designs[$side] = $designFile->toArray();
            $this->_designs[$side]['userProjectId'] = $this->_userProject->id;
        }

        $this->attachDesignFiles($this->_designs, $invoiceItemId);

        // Remove temporary pdf storage
        \App\Core\Utility\File::rmRf($tmpDir);

        $this->_userProject->status = 'finished';
        $this->_userProject->save();

        return $designFiles;
    }


    /**
     * attachDesignFiles
     *
     * @param array $designs
     * @param null $invoiceItemId
     * @return void
     * @access public
     */
    public function attachDesignFiles(array $designs, $invoiceItemId = null)
    {
        if (null === $invoiceItemId) {
            $invoiceItemId = Session::get('invoice_item_id');
        }
        $invoiceItem = Item::find($invoiceItemId);
        $invoiceItem->removeDesignFiles();
        foreach ($designs as $side => $design) {
            $invoiceItem->addDesignFile(DesignFile::find($design['id']));
        }
    }

    /**
     * _getUserPrintFileRoot
     *
     * @access private
     * @return string $userPrintFileRoot
     */
    private function _getUserPrintFileRoot()
    {
        $userPrintFileRoot = config('app.server_config.user_print_file_root');
        return realpath($userPrintFileRoot);
    }


    private function _getPageCount($pdfFile)
    {
        $pages = 0;
        $pdf = new Pdf($pdfFile);
        $result = explode(PHP_EOL, $pdf->getData());
        foreach ($result as $info) {
            $infoParts = explode(':', $info);
            if ('numberofpages' == strtolower($infoParts[0])) {
                $pages = trim($infoParts[1]);
                break;
            }
        }
        return $pages;
    }

    /**
     * getPreviewImageClassName
     *
     * Returns class name for preview image div.
     *
     * @return string $className
     */
    public function getPreviewImageClassName()
    {
        $className = 'ec-editor-previewImage';
        // Check for a page 2 (back)
        $orientation = 'landscape';
        foreach ($this->_customizableDesigns as $page => $design) {
            if (2 == $design->orientation) {
                $orientation = 'portrait';
            }
            $sku = $design->product->sku;
        }
        $className .= '-' . $sku . '-' . $orientation;
        return $className;
    }

    /**
     * getPreviewJpgUrl
     *
     * Wrapper to put together the path for a preview JPG of the stock template based on the provided design ID.
     *
     * @static
     * @param int $designId Excopy_Model_EZT2_Design_Customizable_Design.id
     * @access public
     * @return string $jpgUrl relative full URL pointing to the preview JPG.
     */
    public static function getPreviewJpgUrl($designId)
    {
        if (!is_numeric($designId)) {
            return false;
        }
        if (!$design = CustomizableDesign::find($designId)) {
            return false;
        }
        $designFileConfig = config('app.server_config.designFile');

        $jpgUrl = $designFileConfig['thumbRootURL'] . $design->large_thumb_path . '#View=Fit';
        $jpgPath = $designFileConfig['thumbRootURLServer'] . $design->large_thumb_path;
        if (file_exists($jpgPath)) {
            return $jpgUrl;
        }
        return false;
    }

    /**
     * getPreviewPdfUrl
     *
     * Wrapper to put together the path for a preview PDF of the stock template based on the provided design ID.
     *
     * @static
     * @param int $designId Excopy_Model_EZT2_Design_Customizable_Design.id
     * @access public
     * @return string $pdfUrl relative full URL pointing to the preview PDF.
     */
    public static function getPreviewPdfUrl($designId)
    {
        if (!is_numeric($designId)) {
            return false;
        }
        if (!$design = CustomizableDesign::find($designId)) {
            return false;
        }
        $designFileConfig = config('app.server_config.designFile');

        $pdfUrl = $designFileConfig['thumbRootURL'] . $design->pdf_path . '#View=Fit';
        $pdfPath = $designFileConfig['thumbRootURLServer'] . $design->pdf_path;
        if (file_exists($pdfPath)) {
            return $pdfUrl;
        }
        return false;
    }


    /**
     * saveThumbnails
     * @param array $thumbnails
     * @access private
     * @return void
     *
     * method takes in an array of thumbnails consisting of the large and small
     * thumbnails for a given design as returned by mpower and then moves and
     * renames the fils to persist them in the user_project directory for the
     * particular user_project. Method then creates db entries for the
     * user_project_customizable design row to provide a pointer to the new
     * files.
     *
     **/
    private function saveThumbnails($thumbnails)
    {

        foreach ($thumbnails as $size => $thumbnail) {
            foreach ($thumbnail as $side => $thumb) {
                $newPath = str_replace(
                    'template.pf', $side . $size . 'thumbnail.jpg', $this->_userProject->file_path
                );
                \File::copy($thumb, $newPath);
                if ($side == 'front') {
                    $page = 1;
                } else {
                    $page = 2;
                }
                foreach ($this->_userProject->customizableDesigns()->get() as $designLookup) {
                    if ($page == $designLookup->page) {
                        $userProjectCustomizableDesign = $designLookup;
                        break;
                    }
                }

                if ($userProjectCustomizableDesign) {
                    $thumbUrl = str_replace(
                        array('/share/SANweb/ezt2wwwroot', '/home/web/ezt2wwwroot'),
                        '/static/imageserver',
                        $newPath
                    );
                    $userProjectCustomizableDesign->{$size . '_thumb'} = $thumbUrl;
                    $userProjectCustomizableDesign->save();
                }
            }
        }

    }


    private function createProjectFile()
    {
        if (key_exists('1', $this->_designs)) {
            $this->_frontDesign = $this->_designs['1'];
            $this->_sides[1] = 'front';
        }
        if (key_exists('2', $this->_designs)) {
            $this->_backDesign = $this->_designs['2'];
            $this->_sides[2] = 'back';
        }


    }


    private function writeFilesToDisk($directory, $fileName, $contents)
    {
        $fullFileName = $directory . $fileName;
        $fw = fopen($fullFileName, "w");
        if ($fw) {
            $writeResult = fwrite($fw, $contents);
            fclose($fw);
        }

    }

    private function getUserDesignPreferences($appPreference = 'pulse')
    {
        $designPreferences = $this->_user->templatePreferences()->where('app', $appPreference)->get();
        if (is_null($designPreferences)) {
            $designPreferences = $this->_user->templatePreferences()->orderBy('app')->get();
        }
        $useReturnAddress =  $this->_user->pulseSetting->use_return_address;

        $preferenceArray = array();
        if ($designPreferences) {
            foreach ($designPreferences as $designPreference) {
                if (isset($designPreference->first_name) && null != $designPreference->first_name) {
                    $preferenceArray['FirstName'] = $designPreference->first_name;
                }
                if (isset($designPreference->last_name) && null != $designPreference->last_name) {
                    $preferenceArray['LastName'] = $designPreference->last_name;
                }
                if (isset($designPreference->email) && null != $designPreference->email) {
                    $preferenceArray['EmailAddress'] = $designPreference->email;
                }
                if (isset($designPreference->title) && null != $designPreference->title) {
                    $preferenceArray['Title'] = ($useReturnAddress ? $designPreference->title : '');
                }
                if (isset($designPreference->address1) && null != $designPreference->address1) {
                    $preferenceArray['Address1'] = ($useReturnAddress ? $designPreference->address1 : '');
                }
                if (isset($designPreference->address2) && null != $designPreference->address2) {
                    $preferenceArray['Address2'] = ($useReturnAddress ? $designPreference->address2 : '');
                }
                if (isset($designPreference->company) && null != $designPreference->company) {
                    $preferenceArray['CompanyName'] = ($useReturnAddress ? $designPreference->company : '');
                }
                if (isset($designPreference->city) && null != $designPreference->city) {
                    $preferenceArray['City'] = ($useReturnAddress ? $designPreference->city : '');
                }
                if (isset($designPreference->zip) && null != $designPreference->zip) {
                    $preferenceArray['Zip'] = ($useReturnAddress ? $designPreference->zip : '');
                }
                if (isset($designPreference->url) && null != $designPreference->url) {
                    $preferenceArray['WebAddress'] = $designPreference->url;
                }
                if (isset($designPreference->state) && null != $designPreference->state) {
                    $preferenceArray['State'] = ($useReturnAddress ? $designPreference->stateRecord->state_abbr : '');
                }
                if (isset($designPreference->disclaimer) && null != $designPreference->disclaimer) {
                    $preferenceArray['Disclaimer'] = $designPreference->disclaimer;
                }
                if (isset($designPreference->tagline) && null != $designPreference->tagline) {
                    $preferenceArray['Tagline'] = $designPreference->tagline;
                }
                if (isset($designPreference->logo) && null != $designPreference->logo) {
                    $imageRow = Image::find($designPreference->logo);
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $preferenceArray['CompanyLogo'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    }
                }
                if (isset($designPreference->logo_2) && null != $designPreference->logo_2) {
                    $imageRow = Image::find($designPreference->logo_2);
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $preferenceArray['TitleLogo'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    }
                }
                if (isset($designPreference->mugshot) && null != $designPreference->mugshot) {
                    $imageRow = Image::find($designPreference->mugshot);
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $preferenceArray['Headshot1'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    }
                }
                if (isset($designPreference->bug1) && null != $designPreference->bug1) {
                    $imageRow = Image::find($designPreference->bug1);
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $preferenceArray['Bug1'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    }
                }
                if (isset($designPreference->bug2) && null != $designPreference->bug2) {
                    $imageRow = Image::find($designPreference->bug2);
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $preferenceArray['Bug2'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    }
                }

                $phonesXref = $designPreference->phonesXref();
                if ($phonesXref->count()) {
                    $i = 1;
                    foreach ($phonesXref as $phoneXref) {
                        $preferenceArray["phone$i"] = $phoneXref->phone->phone_number;
                        $preferenceArray["phone$i" . 'label'] = $phoneXref->phone->phone_type;
                        $i++;
                    }
                }
            }
        }

        return $preferenceArray;
    }


    private function _mapVariables($input)
    {
        $mapped = array();
        $useReturnAddress =  (isset($input['use_return_address']) ? $input['use_return_address'] : $this->_user->pulseSetting->use_return_address);

        foreach ($input as $key => $value) {
            switch (strtolower($key)) //Needs to work with lower and uppercase input keys
            {
                case 'just_sold_heading':
                case 'just_listed_heading':
                    $mapped['FrontHeadline'] = $value;
                    $mapped['BackHeadline'] = $value;
                    break;
                case 'just_sold_subhead':
                case 'just_listed_subhead':
                    $mapped['FrontSubhead'] = $value;
                    $mapped['BackSubhead1'] = $value;
                    $mapped['Subhead'] = $value;
                    break;
                case 'just_sold_message':
                case 'just_listed_message':
                    $mapped['FrontMessage1'] = $value;
                    $mapped['BackMessage1'] = $value;
                    $mapped['Message'] = $value;
                    break;
                case 'first_name':
                    $mapped['FirstName'] = $value;
                    break;
                case 'last_name':
                    $mapped['LastName'] = $value;
                    break;
                case 'email':
                    $mapped['EmailAddress'] = $value;
                    break;
                case 'title':
                    $mapped['Title'] = ($useReturnAddress ? $value: '');
                    break;
                case 'address1':
                    $mapped['Address1'] = ($useReturnAddress ? $value: '');
                    break;
                case 'address2':
                    $mapped['Address2'] = ($useReturnAddress ? $value: '');
                    break;
                case 'company':
                    $mapped['CompanyName'] = ($useReturnAddress ? $value: '');
                    break;
                case 'city':
                    $mapped['City'] = ($useReturnAddress ? $value: '');
                    break;
                case 'zip':
                    $mapped['Zip'] = ($useReturnAddress ? $value: '');
                    break;
                case 'url':
                    $mapped['WebAddress'] = $value;
                    break;
                case 'state':
                    if ($useReturnAddress) {
                        if ($value) {
                            $mapped['State'] = States::find($value)->state_abbr;
                        } else {
                            $mapped['State'] = $value;
                        }
                    } else {
                        $mapped['State'] = '';
                    }
                    break;
                case 'disclaimer':
                    $mapped['Disclaimer'] = $value;
                    break;
                case 'tagline':
                    $mapped['Tagline'] = $value;
                    break;
                case 'logo':
                case 'CompanyLogo':
                    $imageRow = Image::find(intval($value));
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $mapped['CompanyLogo'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    } else {
                        $mapped['CompanyLogo'] = $value;
                    }
                    break;
                case 'logo_2':
                case 'TitleLogo':
                    $imageRow = Image::find(intval($value));
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $mapped['TitleLogo'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    } else {
                        $mapped['TitleLogo'] = $value;
                    }
                    break;
                case 'mugshot':
                case 'Headshot1':
                    $imageRow = Image::find(intval($value));
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $mapped['Headshot1'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    } else {
                        $mapped['Headshot1'] = $value;
                    }
                    break;
                case 'bug1':
                case 'Bug1':
                    $imageRow = Image::find(intval($value));
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $mapped['Bug1'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    } else {
                        $mapped['Bug1'] = $value;
                    }
                    break;
                case 'bug2':
                case 'Bug2':
                    $imageRow = Image::find(intval($value));
                    if ($imageRow && !is_null($imageRow->filename)) {
                        $mapped['Bug2'] = $imageRow->filepath . '\\' . $imageRow->filename;
                    } else {
                        $mapped['Bug2'] = $value;
                    }
                    break;
                default:
                    $mapped[$key] = $value;
            }

            if (substr($key, 0, 5) == 'phone' && substr($key, -5, 5) == '_type') {
                $mapped['phone'.substr($key,5,1).'label'] = (
                    '' == $input[$key] ? $input[$key] : PhoneType::find($value)->phone_label
                );
            }
        }

        return $mapped;
    }

    public static function callBackFilterJlVar($val)
    {
        if (strpos($val, 'just_sold_') !== false) {
            return false;
        } else {
            return true;
        }
    }

    public static function callBackFilterJsVar($val)
    {
        if (strpos($val, 'just_listed_') !== false) {
            return false;
        } else {
            return true;
        }
    }

    public static function fetchProjectsFromQueue()
    {
        // fetch projects when ready
        foreach (['jlQueueId' => 'jlProjectId', 'jsQueueId' => 'jsProjectId'] as $queueType => $projectType) {
            if (session()->has($queueType)) {
                // check job queue for finished pf project
                $jobQueue = (new JobQueue)->find(session()->get($queueType));
                $jobQueue->getJobResult();
                if ('done' == $jobQueue->status) {
                    session([$projectType => $jobQueue->getResult()['projectId']]);
                }
                session()->forget($queueType);
            }
        }
    }
}