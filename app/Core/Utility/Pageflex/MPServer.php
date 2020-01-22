<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 5/3/16
 * Time: 4:30 PM
 */

namespace App\Core\Utility\Pageflex;
    /**
     * Pageflex and Mpower API
     *
     * @package PageflexPHP
     * @version $Id$
     */
use App\Core\Models\OrderCore\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Config;

/**
 * The MPServer class.
 *
 * The MPServer class handles the communication with the
 * Pageflex Mpower server. Besides the set/get methods which steer the
 * connection properties, the {@link submit()} method allows submitting a Pageflex job to the server.
 *
 * Note - error conditions are always returned through a PHP Exception
 *
 */

class MPServer
{
    // Server name or IP address
    private $_server;
    // Server port
    private $_port;
    // Connection timeout
    private $_connectionTimeout;
    // TCP/IP socket where the Mpower server is accepting connections on
    private $_socket;
    // Last submitted MPJob object.
    private $_currJob;

    /**
     * Create and initialize an MPServer object
     *
     * @param string $server Name or IP address of host where Pageflex Mpower server is running
     * @param int $port TCP/IP port where Pageflex Mpower server is listening on for connection requests.
     * @param int $connectionTimeout Connection timeout (in seconds)
     */
    public function __construct($server = "localhost", $port = 8008, $connectionTimeout = 10)
    {
        $this->_server = $server;
        $this->_port = $port;
        $this->_connectionTimeout = $connectionTimeout;
    }


    /**
     * Submit a job to the Pageflex Mpower server
     *
     * @param MPJob $mpJob MPJob object to submit. Can be null, in which case an empty default one is created.
     * @param Project $pfProject PFProject object for which to submit a job.
     * @param Job $pfJob The PFJob (part of $pfProject) to submit.
     *
     * @return MPJob object where the properties that reflect the job outcome are set (pages/documents rendered, jobid).
     *
     * public function submit(MPJob $mpJob=null, PFProject $pfProject, PFJob $pfJob) {
     * @throws \Exception
     */
    public function submit(MPJob $mpJob = null, $pfProject = null, $pfJob = null)
    {

        $timeStart = time();

        try {
            // No MPJob - create an empty one
            if ($mpJob == null) {
                $mpJob = new MPJob();
            }

            $this->_currJob = $mpJob;
            // Create the XML to submit to the Pageflex MPower server.cd ../
            $mpServerJob = $mpJob->buildMPServerJob($pfProject, $pfJob);
            // Connect to the MPower server, submit the job and disconnect.
            $this->mpowerConnect();
            $this->mpowerSubmit($mpServerJob);
            $this->mpowerDisconnect();
        } catch (\Exception $e) {
            $errorLog = new Log();
            $errorLog->source = "Pageflex";
            $errorLog->date_created = Carbon::now();
            $errorLog->message = $e->getMessage();
            $errorLog->save();
        }

        $duration = time() - $timeStart;
        $config = Config('app.server_config');
        $maxTime = $config['designServer']['notificationThreshold'];
        if ($duration > $maxTime) {
            $errorLog = new Log();
            $errorLog->source = "Pageflex";
            $errorLog->date_created = Carbon::now();
            $errorLog->message = "A rendering job took longer than threshold set. Duration (seconds): " . $duration;
            $errorLog->message .= ". MpJobId: " . $this->_currJob->getMpJobId();
            $errorLog->save();
        }

        // rethrow exception
        if (isset($e)) {
            throw $e;
        }

        // Return new or updated MPJob with submission feedback set.
        return $this->_currJob;
    }


    /*
     * Connect to Mpower server.
     */
    private function mpowerConnect()
    {
        $errno = 0;
        $errstr = "";
        $this->_socket = @fsockopen($this->_server, $this->_port, $errno, $errstr, $this->_connectionTimeout);
        if (!$this->_socket) {
            @fclose($this->_socket);
            throw new \Exception(
                sprintf(
                    "[MPServer::mpowerConnect]Failed to connect to %s:%s.Errno:%d:%s",
                    $this->_server, $this->_port, $errno, $errstr
                )
            );
        }

    }

    /*
     * Disconnect from Mpower server
     */
    private function mpowerDisconnect()
    {
        if (!fclose($this->_socket)) {
            //Would like to know why this failed but how will php tell me?
            throw new \Exception(
                sprintf(
                    "[MPServer::mpowerDisconnect]Failed to disconnect from %s:%s." .
                    " Unfortunately the reason is unknown",
                    $this->_server, $this->_port
                )
            );
        }
    }

    /*
     * Submit the job to the Mpower server.
     */
    private function mpowerSubmit($pfJobInput)
    {
        // Send the job
        $bytesWritten = fwrite($this->_socket, $pfJobInput);
        if (!$bytesWritten) {
            //Would like to know why this failed but how will php tell me?
            throw new \Exception("[MPServer::mpowerSubmit]Socket fwrite failed");
        }
        // Wait for it to finish and read job log.
        $reply = '';
        $timeoutInSeconds = 90;
        $start = time();
        while (!feof($this->_socket)) {
            $reply .= fread($this->_socket, 8192);
            $current = time();
            if ($current - $start > $timeoutInSeconds) {
                Logger::debug('Timeout of ' . $timeoutInSeconds . ' reached during MPower job submission, terminating.');
                break;
            }
        }
        // Try to make sense of the job log (only look for job id, documents and pages rendered)
        $success = strpos($reply, "Job Status = SUCCESS");
        //
        $lines = explode("\n", $reply);
        if (count($lines) < 17) {
            Logger::debug('Response: ' . print_r($lines, true));
        }
        if ($success) {
            $statInfoLine = explode(' ', $lines[count($lines) - 2]);
            $this->_currJob->setMpJobId($statInfoLine[2]);
            $jobInfoLine = explode(' ', $lines[count($lines) - 3]);
            $this->_currJob->setDocumentCount($jobInfoLine[4]);
            $this->_currJob->setPageCount($jobInfoLine[7]);
        }
        if (!$success) {
            throw new \Exception("[MPServer::mpowerSubmit]Job failure." . $reply);
        }

    }
    
    
    
}