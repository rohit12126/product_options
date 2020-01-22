<?php

namespace App\Core\Traits;

/**
 * Class DisableRememberTokenTrait
 * @package App\Core\Traits
 *
 * Trait for disabling the remember me functionality used by Laravel.
 * This is required in order to use the Auth gate on User model without remember_token column
 *
 */
trait DisableRememberTokenTrait {

    /**
     * @return null
     */
    public function getRememberToken()
    {
        return null; // not supported
    }

    /**
     * @param $value
     */
    public function setRememberToken($value)
    {
        // not supported
    }

    /**
     * @return null
     */
    public function getRememberTokenName()
    {
        return null; // not supported
    }

    /**
     * Overrides the method to ignore the remember token.
     * @param $key
     * @param $value
     */
    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();
        if (!$isRememberTokenAttribute)
        {
            parent::setAttribute($key, $value);
        }
    }
}