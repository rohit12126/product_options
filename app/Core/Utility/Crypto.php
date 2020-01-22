<?php
/**
 * Created by PhpStorm.
 * User: wjacobsen
 * Date: 6/14/16
 * Time: 2:39 PM
 */

namespace App\Core\Utility;


class Crypto
{

    /**
     * Method of encryption used by mcrypt_encrypt()
     * See: http://www.php.net/mcrypt
     *
     * @var string
     */
    private $_cipher = MCRYPT_RIJNDAEL_256;

    /**
     * Key used for encryption/decryption.
     *
     * @var string
     */
    private $_key;

    /**
     * One of pre-defined modename constants.
     * See: http://www.php.net/mcrypt
     *
     * @var string
     */
    private $_mode = MCRYPT_MODE_ECB;

    /**
     * One of pre-defined random source generator constant.
     * See: http://www.php.net/mcrypt
     *
     * @var string
     */
    private $_randSource = MCRYPT_RAND;

    /**
     * IV object
     * See: http://www.php.net/mcrypt
     *
     * @var object
     */
    private $_iv;

    /**
     * Text to encrypt/decrypt.
     *
     * @var string
     */
    private $_text;

    /**
     * Constructor: Calls internal method for generating IV for internal use.
     */
    public function __construct()
    {
        $this->_generateIv();
    }

    /**
     * Encrypts a string with a hash of the combined secret key and provided seed.
     *
     * @param string $key
     * @param string $text
     * @param bool $returnBase64
     * @return string
     * @throws \Exception
     */
    public function encrypt($key = '', $text = '', $returnBase64 = true)
    {
        try {
            $this->_setKey($key);
            $this->_setText($text);
        } catch (\Exception $e) {
            throw $e;
        }

        $prev = error_reporting();
        error_reporting(E_ALL & ~E_DEPRECATED);
        $encryptedText = mcrypt_encrypt($this->_cipher, $this->_key, $this->_text, $this->_mode, $this->_iv);
        error_reporting($prev);

        if ($returnBase64 === true) {
            return base64_encode($encryptedText);
        } else {
            return $encryptedText;
        }
    }

    /**
     * Decrypts a string with a hash of the combined secret key and provided seed.
     *
     * @param string $key
     * @param string $text
     * @return string
     * @throws \Exception
     */
    public function decrypt($key = '', $text = '')
    {
        // If text is base64 encoded, then decode.
        if (preg_match('/^[A-z0-9\+\/]{43}\=$/', $text)) {
            $text = base64_decode($text);
        }

        try {
            $this->_setKey($key);
            $this->_setText($text);
        } catch (\Exception $e) {
            throw $e;
        }

        $prev = error_reporting();
        error_reporting(E_ALL & ~E_DEPRECATED);
        $decryptedText = mcrypt_decrypt($this->_cipher, $this->_key, $this->_text, $this->_mode, $this->_iv);
        error_reporting($prev);
        //Return trimmed string as decryption adds padding.
        return trim($decryptedText);
    }

    /**
     * Internal function for creating mcrypt IV
     * See: http://www.php.net/mcrypt_encrypt
     */
    private function _generateIv()
    {
        $prev = error_reporting();
        error_reporting(E_ALL & ~E_DEPRECATED);
        $ivSize = mcrypt_get_iv_size($this->_cipher, $this->_mode);
        $this->_iv = mcrypt_create_iv($ivSize, $this->_randSource);
        error_reporting($prev);
    }

    /**
     * Validates provided key to encrypt/decrypt
     *
     * @param string $key
     * @throws \Exception
     */
    private function _setKey($key = '')
    {
        if (!is_string($key)) {
            throw new \Exception('string expected.');
        } else {
            $key = trim($key);
            if (empty($key)) {
                throw new \Exception('key to encrypt required.');
            } else {
                $this->_key = $key;
            }
        }
    }

    /**
     * Validates provided text to encrypt/decrypt
     *
     * @param string $text
     * @throws \Exception
     */
    private function _setText($text = '')
    {
        if (!is_string($text)) {
            throw new \Exception('string expected.');
        } else {

            if (empty($text)) {
                throw new \Exception('text to encrypt required.');
            } else {
                $this->_text = $text;
            }
        }
    }

    /**
     * Destructor: cleans up IV object
     */
    public function __destruct()
    {
        $this->_iv = null;
    }
    
}