<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 3/24/17
 * Time: 1:36 PM
 */

namespace App\Core\Service;

use Ups\Entity\Address;

class Satori
{

    const TOLERANCE_STRICT = 0;
    const TOLERANCE_MODERATE = 1;
    const TOLERANCE_RELAXED = 2;

    private $_socket;
    private $_evd = 0;
    private $_presort = false;
    private $_allowedFailCompIds = array();

    public function create($evData, $presort = false)
    {
        $this->_presort = $presort;
        $this->_evd = $evData;
        $this->_socket = socket_create(AF_INET, SOCK_STREAM, 0);
    }

    public function connect($host, $port, $timeout = null)
    {
        ini_set("default_socket_timeout", $timeout);
        if (!is_null($timeout) && !is_null($this->_socket)) {
            socket_set_option($this->_socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $timeout, "usec" => 0));
            socket_set_option($this->_socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
        }
        if (!@socket_connect($this->_socket, $host, $port)) {
            throw new \Exception("Unable to connect socket to Mrtk");
        }
    }

    public function prepareTask($key)
    {
        $in = ($this->_presort ? "STI1=" : "BTI1=");
        $data = "\t0\t$key\n";
        return $this->_sendData($in, $data);
    }

    private function _sendData($in, $data)
    {
        $sLength = strlen($data);
        $in .= $sLength . $data;
        socket_write($this->_socket, $in, strlen($in));
        return socket_read($this->_socket, 2048);
    }

    public function setProperty($key, $value)
    {
        $in = ($this->_presort ? "STI8=" : "BTI8=");
        $data = "\t$key\t$value\n";
        return $this->_sendData($in, $data);
    }

    public function close()
    {
        $in = ($this->_presort ? "STI5=" : "BTI5=");
        $data = "\t\n";
        $this->_sendData($in, $data);
        socket_close($this->_socket);
    }

    public function certifyAddress(Address $address)
    {
        $in = "ZTI5=";
        $data[] = (isset($address->id) ? $address->id : 0);
        $data[] = config('app.server_config.mailRoomToolKitKey');
        $data[] = '';
        $data[] = $address->getAddressLine1();
        $data[] = $address->getAddressLine2();
        $data[] = $address->getCity();
        $data[] = $address->getStateProvinceCode();
        $data[] = $address->getPostalCode();
        $data[] = '';
        $data[] = '';
        $data[] = 'N';
        $dataString = implode("\t", $data);
        $out = $this->_sendData($in, "\t" . $dataString . "\n");
        $lose = explode("	", $out);
        $test = $lose[0];
        $length = strlen($test);
        $result = substr($out, $length);
        $result = ltrim($result);
        $result = str_replace("\\", "", $result);
        $result = rtrim($result);
        $thisLine = explode("\t", $result);
        socket_close($this->_socket);
        $address->setAddressLine1($thisLine[3]);
        $address->setAddressLine2($thisLine[4]);
        $address->setCity($thisLine[5]);
        $address->setStateProvinceCode($thisLine[6]);
        $address->setPostalCode(substr($thisLine[7], 0, 5));
        return array('address' => $address, 'status' => $thisLine[13], 'message' => $thisLine[10]);
    }
}