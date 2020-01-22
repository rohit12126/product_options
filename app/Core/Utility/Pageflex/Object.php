<?php
namespace App\Core\Utility\Pageflex;

    /**
     * Pageflex and Mpower API
     * @package PageflexPHP
     * @version $Id$
     */

/**
 * The Object class is an abstract base class for al "PF"
 * objects. It manages the common properties of Pageflex objects.
 *
 * @since 1.2
 */

abstract class Object
{
//  Array which maps user property names to property values - maintained by the "user" of the PFObject class.
    private $_userProperties;

    /**
     * Get object's user properties
     *
     * Note that the properties are not available in the Pageflex project file. They are therefore
     * not persisted when saving a project file nor loaded when loading from a file. The property values
     * after loading are empty.
     *
     * @return array User properties mapping property names to values
     *
     * @since 1.2
     */
    public function getUserProperties()
    {
        return $this->_userProperties;
    }

    /**
     * Set object's user properties
     *
     * Set user properties for the object.
     *
     * Note that the properties are not available in the Pageflex project file. They are therefore
     * not persisted when saving a project file nor loaded when loading from a file. The property values
     * after loading are empty.
     *
     * @param $userProperties
     * @return void $userProperties User properties mapping property names to values
     *
     * @since 1.2
     */
    public function setUserProperties($userProperties)
    {
        $this->_userProperties = $userProperties;
    }

    /**
     * Return value of a user property.
     *
     * @param string $name Name of property for which to get value
     * @return mixed Value of requested property or null.
     *
     * @since 1.2
     */
    public function getUserProperty($name)
    {
        if ($this->_userProperties != null) {
            return $this->_userProperties[$name];
        } else {
            return null;
        }
    }

    /**
     * Set value of a user property
     *
     * @param string $name Name of property for which to set the value
     * @param mixed $value Value of property to set
     *
     * @since 1.2
     */
    public function setUserProperty($name, $value)
    {
        $this->_userProperties[$name] = $value;
    }

    /**
     * Checks whether given property is set
     *
     * @param $name
     * @return bool True when property is set, else false
     *
     * @since 1.2
     */
    public function hasProperty($name)
    {
        if ($this->_userProperties != null && $this->_userProperties[$name] != null) {
            return true;
        } else {
            return false;
        }

    }
}