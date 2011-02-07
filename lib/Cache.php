<?php
/**
 * Contains a class for caching things programmatically in Wordpress.
 *  Great for caching things that are a little expensive, like pulling a
 *  Twitter feed or something else.
 *
 * Usage:
 *  # $bigthing is some object that took a long time to get/compute with
 *  # get_bigthing. We want to cache it for an hour or so.
 *
 *  if(!($bigthing = WPEasyCache::get('bigthing', FALSE)))
 *  {
 *      $bigthing = get_bigthing();
 *      WPEasyCache::set('bigthing', $bigthing, 60*60); # Expire in 1 hour
 *  }
 *
 *  # Do some stuff with $bigthing
 *
 * @author Kenny Katzgrau <katzgrau@gmail.com>
 * @link https://github.com/katzgrau/WP-Easy-Cache
 */

/**
 * WPEasyCache - A class for quickly caching strings n' things (or any object)
 *  in Wordpress. Uses the Wordpress options functions as the backend.
 */
class WPGH_WPEasyCache
{
    const CACHE_PREFIX          = 'WPEASYCACHE_';
    const MAX_KEY_LENGTH        = 32;

    private static $_isWPLoaded = FALSE;

    /**
     * Get an item from the cache by key
     * @param string $key The name the name of the cache item
     * @param bool $force Force a value if it exists, even if it's expired
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = FALSE, $force = FALSE)
    {
        $key   = self::_prepareKey($key);
        $value = self::_getOption($key, FALSE);

        # No value at all? return the default
        if($value === FALSE) return $default;

        # Now we must have a value. Unserialize and check expiration.
        $value = @json_decode($value);

        # Uh oh, couldn't unserialize
        if(!is_object($value)) throw new Exception("$key value wasn't decodable.");

        $expire = $value->expire;
        $value  = $value->value;

        # No expiration?
        if($expire === FALSE) return $value;

        # Expired?
        if(time() < $expire)
            return $value;
        else
            return $default;
    }

    /**
     * Set a cache key item
     * @param string $key
     * @param mixed $value
     * @param int $expire Number of seconds to expire in. Default is no expiration (FALSE)
     */
    public static function set($key, $value, $expire = FALSE)
    {
        $key   = self::_prepareKey($key);
        $cache = array (
            'value'  => $value,
            'expire' => ($expire === FALSE ? $expire : time() + $expire)
        );

        $cache = json_encode($cache);

        self::_setOption($key, $cache);
    }

    public static function delete($key)
    {
        throw new Exception("Not implemented yet");
    }

    public static function flush()
    {
        throw new Exception("Not implemented yet");
    }

    /**
     * Prepare a string to be used as a cache key
     * @param string $key
     */
    private static function _prepareKey($key)
    {
        if(!is_string($key))
            throw new Exception('Key must be a string');

        $key = preg_replace('/[^a-zA-Z0-9\-_\.]/s', '#', $key);

        if(strlen($key) > self::MAX_KEY_LENGTH)
            $key = substr($key, 0, self::MAX_KEY_LENGTH);

        $key = self::CACHE_PREFIX . $key;

        return $key;
    }

    /**
     * Sets a Wordpress option
     * @param string $name The name of the option to set
     * @param string $value The value of the option to set
     */
    private static function _setOption($name, $value)
    {
        self::_checkWP();

        if (get_option($name) !== FALSE)
        {
            update_option($name, $value);
        }
        else
        {
            add_option($name, $value);
        }
    }

    /**
     * Gets a Wordpress option
     * @param string    $name The name of the option
     * @param mixed     $default The default value to return if one doesn't exist
     * @return string   The value if the option does exist
     */
    private static function _getOption($name, $default = FALSE)
    {
        self::_checkWP();

        $value = get_option($name);
        if( $value !== FALSE ) return $value;
        return $default;
    }

    /**
     * Check to see if Wordpress is laoded, throw an exception if it isn't
     * @throws Exception
     */
    private static function _checkWP()
    {
        if(self::$_isWPLoaded) return;

        if(!function_exists('get_option'))
            throw new Exception ('Wordpress must be fully loaded before using ' . __CLASS__);

        self::$_isWPLoaded = TRUE;
    }

}