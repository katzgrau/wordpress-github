<?php
/**
 * This file contains a class for making webservice calls. Pulled from
 *  the WPSearch 2 project
 *
 * @author Kenny Katzgrau <katzgrau@gmail.com>
 */

/**
 * Facilitates HTTP GET, POST, PUT, and DELETE calls using cURL as a backend. For
 *  GET, will fallback to file_get_contents
 */
class WPGH_Net
{
    /**
     * Fetch a web resource by URL
     * @param string $url The HTTP URL that the request is being made to
     * @param array  $options Any PHP cURL options that are needed
     * @return object An object with properties of 'url', 'body', and 'status'
     */
    public static function fetch($url, $options = array())
    {
        if(!function_exists('curl_exec'))
        {
            if(!$options) return file_get_contents ($url);
            else return '';
        }

        $curl_handle  = curl_init($url);
        $curl_version = curl_version();

        $options    += array(CURLOPT_RETURNTRANSFER => true);
        $options    += array(CURLOPT_USERAGENT      => "curl/".$curl_version[version]);

        curl_setopt_array($curl_handle, $options);

        $timer = "Call to $url via HTTP";

        $body   = curl_exec($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        return $body;
    }

    /**
     * Issues an HTTP GET request to the specified URL
     * @param string $url
     * @return object An object with properties of 'url', 'body', and 'status'
     */
    public static function get($url)
    {
        return self::fetch($url);
    }

    /**
     * Issues an HTTP POST request to the specified URL with the supplied POST
     *  body
     * @param string $url
     * @param string $data The raw POST body
     * @return object An object with properties of 'url', 'body', and 'status'
     */
    public static function post($url, $data)
    {
        return self::fetch($url, array (
                                        CURLOPT_POST       => true,
                                        CURLOPT_POSTFIELDS => $post_data
                                       )
                          );
    }

    /**
     * Issues an HTTP DELETE to the specified URL
     * @param string $url
     * @return object An object with properties of 'url', 'body', and 'status'
     */
    public static function delete($url)
    {
        return self::fetch($url, array (
                                        CURLOPT_CUSTOMREQUEST => 'DELETE'
                                      ));
    }

    /**
     * Issues an HTTP PUT to the specified URL
     * @param string $url
     * @param string $data Raw PUT data
     * @return object An object with properties of 'url', 'body', and 'status'
     */
    public static function put($url, $data)
    {
        $putData = tmpfile();

        fwrite($putData, $data);
        fseek($putData, 0);

        $result = self::fetch($url, array(
                                          CURLOPT_PUT        => true,
                                          CURLOPT_INFILE     => $putData,
                                          CURLOPT_INFILESIZE => strlen($putData)
                                        ));
        fclose($putData);

        return $result;
    }
}