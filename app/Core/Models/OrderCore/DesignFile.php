<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/9/17
 * Time: 1:52 PM
 */

namespace App\Core\Models\OrderCore;


use App\Core\Models\BaseModel;
use App\Core\Models\EZT2\User\Project;
use App\Core\Models\OrderCore\DesignFile\Data;
use App\Core\Models\OrderCore\DesignFile\UserProject;
use App\Core\Utility\FilePath;
use App\Core\Utility\File as FileUtil;
use App\Core\Utility\UserPath;
use Exception;

class DesignFile extends BaseModel
{
    const DEFAULT_BASENAME = 'converted.pdf';
    const UPDATED_AT = null; // work-around

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'design_file';

    protected $guarded = [];

    /**
     * Lets the caller customize the behavior.
     */
    public $options = array('thumbnails' => true);

    /**
     * Explicitely set these vars so that Eloquent doesn't attempt to persist them.
     * @var
     */
    public $tmpPdf;
    public $tmpUpload;
    public $skipThumbs = false;
    private $_useTmpFilesDir;
    private $_tmpFilesDir;

    public function scopeFront($query)
    {
        return $query->where('page', '=', '1');
    }

    public function scopeBack($query)
    {
        return $query->where('page', '=', '2');
    }

    public function save(array $options = [])
    {

        if (!$this->path) {
            $this->path = FilePath::calculatePath();
        }

        if ($this->tmpPdf) {
            $this->_setUpTmpFilesDir();
            $this->file = self::DEFAULT_BASENAME;
            $targetPath = $this->getFilesDir() . DIRECTORY_SEPARATOR . $this->file;
            $this->tmpPdf->copyTo($targetPath);
            $this->tmpPdf->delete();
            $this->tmpPdf = null;
            if ($this->tmpUpload) {
                $this->tmpUpload->copyTo($this->getUploadedPath());
            }
            if ($this->type != 'customized' && !$this->skipThumbs) {
                $this->generateThumbnails();
            }
        }

        if (parent::save()) {
            if ($this->_useTmpFilesDir) {
                $this->_useTmpFilesDir = false;
                if (!$this->getRootDir()) {
                    throw new Exception("Root directory is not set");
                }
                if (!file_exists($this->getRootDir())) {
                    throw new Exception(
                        "Root directory \"" . $this->getRootDir() . "\" does not exist"
                    );
                }
                if (!file_exists(dirname($this->getFilesDir()))) {
                    mkdir(dirname($this->getFilesDir()), 0775, true);
                }
                $result = rename($this->_tmpFilesDir, $this->getFilesDir());
            } else {
                $this->_makeFilesDir();
            }
        }
    }


    public function setUpdatedAt($value)
    {
        ;
    }


    public function saveUploadFile($upload, $files, $singleFile, $fileNumber, $userId, $originalFile)
    {
        $this->tmpUpload = $upload;
        $this->tmpPdf = $singleFile;
        $this->user_id = $userId;

        if ($originalFile) {
            $file = new FileUtil($originalFile);
            $ext = $file->ext();
        }
        if (!$originalFile) {
            $this->name = "Uploaded";
        } else {
            $filename = basename($originalFile);
            if (!is_null($ext)) {
                $this->name = substr($filename, 0, -strlen($ext));
            } else {
                $this->name = $filename;
            }
            if ($files && $originalFile && count($files) > 1) {
                $this->name .= ' ' . ($fileNumber + 1);
                $this->page = ($fileNumber + 1);
            }
            // Store original filename.
            $this->original_filename = FileUtil::fixFilename($filename);
        }

        $this->save();
        // Move generic file if there was an original filename.
        if ($this->original_filename && file_exists($this->getFilesDir() . DIRECTORY_SEPARATOR . 'uploaded')) {
            rename(
                $this->getFilesDir() . DIRECTORY_SEPARATOR . 'uploaded',
                $this->getFilesDir() . DIRECTORY_SEPARATOR . $this->original_filename
            );
        }
        return $this->id;
    }


    public function setDataValue($name, $value)
    {
        if (is_null($data = $this->getData($name))) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->design_file_id = $this->id;
        $data->save();
    }

    public function getData($name = null)
    {
        $this->hasMany(Data::class, 'design_file_id', 'id')->where('name', $name);

    }


    public function generateThumbnails()
    {
        if (!array_key_exists('thumbnails', $this->options)) {
            return true;
        }

        $thumbSpecs = array(
            array('name'        => 'large',
                  'defaultSize' => '0x100',
                  'defaultType' => 'image/jpg'),
            array('name'        => 'small',
                  'defaultSize' => '0x50',
                  'defaultType' => 'image/jpg')
        );

        $config = config('app.server_config');

        foreach ($thumbSpecs as $spec) {
            $dims = $config['designFile']['mobile_'.$spec['name'] . 'ThumbnailDimensions'];
            if (empty($dims)) {
                $dims = $spec['defaultSize'];
            }
            $mimeType = (isset($config['designFile'][$spec['name'] . 'ThumbnailType'])) ?
                $config['designFile'][$spec['name'] . 'ThumbnailType'] : [];
            if (empty($mimeType)) {
                $mimeType = $spec['defaultType'];
            }
            /*
             * Calculate the file extension.
             */
            if ($mimeType == 'image/jpg') {
                $ext = 'jpg';
            } else {
                throw new Exception("Unsupported mime-type: '$mimeType'");
            }
            $filename = $spec['name'] . '-thumb.' . $ext;

            $file = new FileUtil($this->getFilesDir() . DIRECTORY_SEPARATOR . $filename);

            $fupThumb = new \stdClass();
            $fupThumb->path = $file->path();

            $fupSrc = new \stdClass();
            $temp = new FileUtil($this->getPdfPath());
            $fupSrc->path = $temp->path();

            try {
                // save to processing server
                $jobQueue = new JobQueue();
                $jobQueue->mime_type = $mimeType;
                $jobQueue->task = 'thumb-generation';

                $jobQueue->data = serialize(
                    array(
                        'filePath'  => (
                            isset($previousFile) && file_exists($previousFile->path) ?
                                $previousFile :
                                $fupSrc
                        ),
                        'thumbPath' => $fupThumb,
                        'thumbSize' => $dims
                    )
                );
                $jobQueue->save();

                // get/wait for return value from processing server
                $jobQueue->getJobResult($config['jobQueueTimeout']);
                $this->{$spec['name'] . '_thumb'} = $filename;
                $previousFile = $fupSrc;
            } catch (Exception $e) {
                $logger = new Log;
                $logger->logError('FRONTEND', $e->getMessage());
            }
            $this->save();
        }
        return true;
    }

    /**
     * Get the full path to the converted PDF file.
     *
     * @return string
     */
    public function getPdfPath()
    {
        return $this->getFilesDir() . DIRECTORY_SEPARATOR . $this->file;
    }

    public function getRootDir()
    {
        if ($this->_useTmpFilesDir) {
            return dirname($this->_tmpFilesDir);
        } else {
            $root = config('app.server_config.user_print_file_root');
            return $root;
        }
    }

    protected function _setUpTmpFilesDir()
    {
        $rootDir = $this->getRootDir();
        $this->_useTmpFilesDir = true;
        $this->_tmpFilesDir = $rootDir . DIRECTORY_SEPARATOR . uniqid('df-tmp-');
        $this->_makeFilesDir();
    }

    protected function _makeFilesDir()
    {
        if (!file_exists($this->getFilesDir())) {
            if (!$this->getRootDir()) {
                throw new Exception("Design File directory is blank");
            }
            if (!file_exists($this->getRootDir())) {
                throw new Exception(
                    "Design File directory \"" . $this->getRootDir() . "\" does not exist!"
                );
            }
            mkdir($this->getFilesDir(), 0775, true);
        }
    }

    public function getFilesDir()
    {
        if ($this->_useTmpFilesDir) {
            return $this->_tmpFilesDir;
        } else {
            return $this->getRootDir() . DIRECTORY_SEPARATOR . $this->getCalculatedPath();
        }
    }

    public function getUploadedPath()
    {
        return $this->getFilesDir() . DIRECTORY_SEPARATOR . 'uploaded';
    }

    public function getCalculatedPath($lastDir = null)
    {
        if ($this->_useTmpFilesDir) {
            return basename($this->_tmpFilesDir);
        } else {
            if (!$this->path) {
                $this->path = FilePath::calculatePath($lastDir);
            }
            return $this->path;
        }
    }

    public function addUserProject(Project $userProject)
    {
        UserProject::create(
            [
                'design_file_id'  => $this->id,
                'user_project_id' => $userProject->id
            ]
        );
    }

}