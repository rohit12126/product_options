<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 11/2/17
 * Time: 2:32 PM
 */

namespace App\Core\Service;


use App\Core\Models\OrderCore\Log;
use Carbon\Carbon;

class ProviderAbstract
{
    protected $_mapping;

    protected function _getProvider($prop)
    {
        return $this->_mapping->where('local', $prop)->first()->provider;
    }

    protected function _getLocal($prop)
    {
        return $this->_mapping->where('provider', $prop)->first()->local;
    }

    protected function _archive($file)
    {
        // copy file to file_archive/monthYear/PulseData/d/file
        try {
            $archivePath = [
                config('app.server_config.fileArchivePath'),
                date('MY'),
                'PulseData',
                date('d')
                ];
            $archivePath = implode(DIRECTORY_SEPARATOR, $archivePath);
            if (!file_exists($archivePath)) {
                mkdir($archivePath, 0755, true);
            }
            copy($file, $archivePath . DIRECTORY_SEPARATOR . basename($file));
        } catch (\Exception $e) {
            $errorLog = new Log();
            $errorLog->source = "Loader";
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage() . ' - ' . $e->getTraceAsString();
            $errorLog->save();
        }
    }

}