<?php namespace App\Core\Utility;

class CookieHelper {

    /**
     * Get the value of a cookie.
     * Optional param to strip special chars.
     *
     * @param $name
     * @param $sanitized
     * @return mixed
     */
    public static function getCookie($name, $sanitized = true)
    {
        $cookie = isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;

        if ($cookie && $sanitized) {
            $cookie = filter_var($cookie, FILTER_SANITIZE_STRING);
        }

        return $cookie;
    }

}