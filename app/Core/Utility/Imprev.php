<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/9/17
 * Time: 2:20 PM
 */

namespace App\Core\Utility;


use App\Core\Models\OrderCore\DesignFile;
use App\Core\Models\OrderCore\Log;
use App\Core\Models\OrderCore\ProductPrintOption;
use Exception;

class Imprev
{
    private $_fileDir;
    private $_filePath;
    private $_destPath;


    public function transferFile($fileData)
    {
        try {
            $fileInfo = $this->_loadFile($fileData['url']);
            if (md5_file($fileInfo['absolutePath']) == $fileData['md5']) {
                $productPrintId = $this->_productPrintId($fileData);
                $designFile = $this->_designFileDB($fileInfo['absolutePath'], $productPrintId, $fileData);
                return $designFile;
            } else {
                throw new Exception('MD5 checksum failed');
            }
        } catch (Exception $e) {
            (new Log())->logError(
                'frontend',
                $e->getMessage()
            );
        }
        return null;
    }

    private function _loadFile($url)
    {
        $config = config('app.server_config');
        $this->_fileDir = $config['user_print_file_root'];
        $this->_filePath = UserPath::calculate(time(), 'df');
        $this->_destPath = $this->_fileDir . DIRECTORY_SEPARATOR . $this->_filePath;
        $fileExt = 'pdf';

        //creat default file name
        $newFileName = $this->_destPath . '/uploaded.' . $fileExt;
        if (!file_exists($this->_destPath)) {
            mkdir($this->_destPath, 0755, true);
        }
        $fp = fopen("$newFileName", "w");
        $options = array(
            CURLOPT_FILE           => $fp,     // return web page
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        if (file_exists($newFileName)) {
            $fileInfo = array('absolutePath' => $newFileName,
                              'relativePath' => $this->_filePath,
                              'filename'     => 'uploaded.' . $fileExt);
            return $fileInfo;
        }
        return null;
    }


    private function _productPrintId($options)
    {
        if (!empty($options['partnerIdMapping'])) {
            $productPrint = ProductPrintOption::where('sku', '=', $options['partnerIdMapping'])->first();
            if (is_null($productPrint)) {
                return 0;
            }
            return $productPrint->id;
        } else {
            $width = $options['pageWidth'] / 72;
            $height = $options['pageHeight'] / 72;

            //always set the smaller value to be the width
            if ($width > $height) {
                $temp = $width;
                $width = $height;
                $height = $temp;
            }
            $productPrintId = 0;

            switch ($options['designType']) {
                case "postcard":
                    if ($width >= 8.5 && $height >= 11) {
                        $productPrintId = 9;
                    } elseif ($width <= 5 && $height <= 6) {
                        $productPrintId = 1;
                    } else {
                        $productPrintId = 2;
                    }
                    break;
                case "card":
                    if ($width >= 7 && $height >= 10) {
                        $productPrintId = 9;
                    }
                    break;
                // newsletter and brochures process as flyers
                case "brochure":
                case "newsletter":
                case "flyer":
                    if ($width >= 11 && $height >= 17) {
                        $productPrintId = 8;
                    } elseif ($width >= 8.5 && $height <= 12) {
                        $productPrintId = 6;
                    } else {
                        $productPrintId = 7;
                    }
                    break;
                case "Business Card":
                    $productPrintId = 5;
                    break;
                case "propertycard":
                    $productPrintId = 12;
                    break;
                case "walkingcard":
                    // Door hanger.
                    $productPrintId = 10;
                    break;
                default:
                    if ($width >= 11 && $height >= 17) {
                        $productPrintId = 8;
                    } elseif ($width >= 8.5 && $height <= 12) {
                        $productPrintId = 6;
                    } else {
                        $productPrintId = 7;
                    }
                    break;
            }
            return $productPrintId;
        }
    }

    /**
     * @param $filePath
     * @param $productPrintId
     * @param array $fileData
     * @return array|null
     * @throws Exception
     */
    private function _designFileDB($filePath, $productPrintId, $fileData = array())
    {
        $token = UserPath::calculate(time(), 'df');
        $tempPath = $this->_fileDir . '/' . $token;
        mkdir($tempPath);
        exec('cd ' . $tempPath . '; pdftk ' . $filePath . ' burst');

        // 3. Retrieve pages
        $docData = file_get_contents($tempPath . DIRECTORY_SEPARATOR . 'doc_data.txt');
        preg_match('/NumberOfPages:\s([0-9]+)/', $docData, $regs);
        $numPages = $regs[1];

        $splitFiles = array();
        for ($i = 1; $i <= $numPages; $i++) {
            $pdfFile = new File($tempPath . '-' . $i . '.pdf');
            rename($tempPath . DIRECTORY_SEPARATOR . sprintf('pg_%04d.pdf', $i), $pdfFile->path());
            $splitFiles[] = $pdfFile;
        }
        if (!isset($fileData['projectName'])) {
            $fileData['projectName'] = 'Partner Supplied ' . time();
        }
        foreach ($splitFiles as $fileNumber => $singleFile) {

            $designFileDataModel = DesignFile::create();
            $designFileDataModel->product_print_id = $productPrintId;
            $designFileDataModel->skipThumbs = true;
            $designFileDataModel->type = 'uploaded';
            $rowIds[] = $designFileDataModel->saveUploadFile(
                '',
                $splitFiles,
                $singleFile,
                $fileNumber,
                $fileData['userId'],
                $fileData['projectName'],
                $token //Added so that we can save the "path" (i.e. 2017/1/19/12/df***
            );
            if (!$designFileDataModel->page) {
                $designFileDataModel->page = 1;
            }
            if (isset($fileData['page']) && $fileData['page'] == 2) {
                $designFileDataModel->page = 2;
            }
            if (isset($fileData['imprevResponseUrl'])) {
                $designFileDataModel->setDataValue('imprevResponseUrl', $fileData['imprevResponseUrl']);
                $designFileDataModel->setDataValue('imprevHostingID', $fileData['hostingID']);
                $designFileDataModel->setDataValue('imprevUserID', $fileData['imprevUserID']);
            }
            $designFileDataModel->save();
        }
        File::rmRf(dirname($tempPath . DIRECTORY_SEPARATOR . 'doc_data.txt'));

        File::rmRf(dirname($filePath));

        if ($rowIds[0]) {
            return $rowIds;
        }
        return null;
    }

    /**
     * Extract the partner integration string from an XML payload.
     */
    public static function getPartnerIntegrationString($userData)
    {
        return (
            isset($userData['@attributes']['partnerIntegrationString']) ?
                $userData['@attributes']['partnerIntegrationString'] :
                null
            );
    }
}