<?php
/**
 * Created by PhpStorm.
 * User: wayne.jacobsen
 * Date: 1/9/17
 * Time: 2:37 PM
 */

namespace App\Core\Utility;


use Exception;

class File
{
    /**
     * The path to the file.
     * @var string
     */
    protected $_path;

    /**
     * The file size in bytes.
     * @var integer
     */
    protected $_size;


    /**
     * Constructor.
     *
     * @param string|array|File The file path
     * @throws Exception
     */
    public function __construct($path)
    {
        if (!$path) {
            throw new Exception("Empty path parameter");
        }
        if ($path instanceof File) {
            $this->_path = $path->path;
        } else {
            if (is_array($path)) {
                if (!array_key_exists('path', $path)) {
                    throw new Exception("Empty path parameter");
                }
                $this->_path = $path['path'];
            } else {
                if (is_string($path)) {
                    $this->_path = $path;
                } else {
                    throw new Exception("Unknown type for \$path");
                }
            }
        }
    }

    /**
     * Copy the file to another location.
     *
     * @param string $path The target path
     * @return File The copied file
     * @throws Exception
     */
    public function copyTo($path)
    {
        if (is_dir($path)) {
            $path = $path . DIRECTORY_SEPARATOR . basename($this->_path);
            $result = copy($this->_path, $path);
        } else {
            $result = copy($this->_path, $path);
        }
        if (!$result) {
            throw new Exception("Failed to copy \"$this->_path\" to \"$path\"");
        }
        $class = get_class($this);
        return new $class(array('path' => $path, 'size' => $this->_size));
    }


    /**
     * Get the path to the file.
     *
     * @return string
     */
    public function path()
    {
        return $this->_path;
    }

    /**
     * The the file size in bytes.
     *
     * @return integer
     */
    public function size()
    {
        if (!isset($this->_size)) {
            $this->_size = filesize($this->_path);
        }
        return $this->_size;
    }

    /**
     * Read the contents from the file.
     *
     * @return string
     */
    public function read()
    {
        return file_get_contents($this->_path);
    }

    /**
     * Write to the file.
     *
     * @param string $contents
     * @return int
     */
    public function write($contents)
    {
        $fd = fopen($this->_path, 'w');
        if ($contents instanceof File) {
            $bytes = fwrite($fd, $contents->read());
        } else {
            $bytes = fwrite($fd, (string)$contents);
        }
        fclose($fd);
        return $bytes;
    }

    /**
     * Delete the file.
     */
    public function delete()
    {
        return unlink($this->_path);
    }

    /**
     * Returns TRUE if file exists.
     *
     * @return boolean
     */
    public function exists()
    {
        return file_exists($this->_path);
    }

    /**
     * Returns the file extension, no leading dot.
     *
     * @todo Change the return value to FALSE for failed match
     * @return string|null
     */
    public function ext()
    {
        $r = File::getExtension($this->_path);
        return $r === false ? null : $r;
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $path
     */
    public static function rmRf($path)
    {
        if (is_dir($path)) {
            $dir = dir($path);
            while (false !== ($entry = $dir->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                self::rmRf($path . DIRECTORY_SEPARATOR . $entry);
            }
            $dir->close();
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    /**
     * Format the passed in filename for the specified system.
     * @param $filename
     * @return mixed|null|string|string[]
     */
    public static function fixFilename($filename)
    {
        // strip everything but basic characters, underscores, periods, spaces and dashes
        $filename = preg_replace('/[^a-z0-9_\s.-]/i', '', $filename);
        // replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        // return formatted filename
        return $filename;
    }


    public static function isAbsolutePath($path)
    {
        return $path[0] == DIRECTORY_SEPARATOR || preg_match('/^[a-z]+:/i', $path);
    }

    public static function getExtension($path)
    {
        if (!$path) {
            return false;
        } else {
            if (preg_match('/\.([^.]+)$/', trim($path), $match)) {
                return $match[1];
            } else {
                return false;
            }
        }
    }
}