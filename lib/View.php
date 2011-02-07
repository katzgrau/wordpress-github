<?php
/**
 * This file contains a class for loading the presentation layer/files
 *
 * @author Kenny Katzgrau <kenny@oconf.org>
 */

/**
 * This class contains methods for loading WPSearch views
 */
class WPGH_View
{
    /**
     * Load a view file. The file should be located in WPSearch/Views.
     * @param string $file The filename of the view without the extenstion (assumed
     *  to be PHP)
     * @param array $data An associative array of data that be be extracted and
     *  available to the view
     */
    public static function load($file, $data = array())
    {
        $file = dirname(__FILE__) . '/../views/' . $file . '.php';

        if(!file_exists($file))
        {
            WPSearch_Log::add('fatal', "View '$file' was not found");
            throw new Exception("View '$file' was not found");
        }

        # Extract the variables into the global scope so the views can use them
        extract($data);

        include($file);
    }
}