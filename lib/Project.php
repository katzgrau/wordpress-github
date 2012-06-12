<?php
/**
 * This file contains a class for fetching, sorting, and representing repository projects
 * @author Kenny Katzgrau <katzgrau@gmail.com>
 */

require_once dirname(__FILE__) . '/Net.php';
require_once dirname(__FILE__) . '/Cache.php';

/**
 * This class represents and fetches projects
 */
class WPGH_Project
{
    /**
     * The URL of the repository (web-viewable)
     * @var string
     */
    public $url;

    /**
     * The name of the repository
     * @var string
     */
    public $name;

    /**
     * The description of the repo
     * @var string
     */
    public $description;

    /**
     * The watcher count for a repo
     * @var int
     */
    public $watchers;

    /**
     * The source hosting the project.. Like GitHub or BitBucket
     * @var string
     */
    public $source;

    /**
     * If the project has one follower, contains 'watcher'. If 0 or greater
     *  than 1, 'watchers'
     * @var string
     */
    public $watcher_noun;

    /**
     * Date of last repo updated
     * @var int
     */
     public $updated;


    /**
     * Fetch information about all of the projects
     * @param string $info A string like github:katzgrau[,bitbucket:katzgrau]+
     * @param string $sort_type Valid sorts include ByWatchers
     * @param bool $sort_asc Whether the sort should be ascending or not
     * @return array[WPGH_Project]
     */
    public static function fetch($info, $sort_type = FALSE, $sort_asc = TRUE)
    {
        $projects = array();
        $sources  = explode(',', $info);

        # Go through the list
        foreach($sources as $source)
        {
            # Check the Cache for each source
            $cache_key = "wpgh:$source";
            if($cache = WPGH_WPEasyCache::get($cache_key))
            {
                $projects = array_merge($projects, $cache);
                continue;
            }

            # Parse out the info
            $source = explode(':', $source);
            if(count($source) != 2) continue;
            list($location, $username) = $source;

            # Prep it to be flexible
            $location = strtolower(trim($location));
            $username = strtolower(trim($username));
            $method = "fetch_$location";

            #It might actually be an option
            if($location == 'sortby')
                $sort_type = $username;

            if($location == 'sortdir')
                $sort_asc = ($username == 'asc');

            # Check that a call exists for this source type
            if(!is_callable(array(__CLASS__, $method)))
                continue;

            # Make the call
            $result = self::$method($username);

            # Good result? Cache it
            if($result !== FALSE)
            {
                WPGH_WPEasyCache::set($cache_key, $result, WPGH_Core::$_cacheExpiration);
            }
            # Bad result? Get the old version back
            else
            {
                $result = WPGH_WPEasyCache::get($cache_key, array(), TRUE);
            }

            $projects = array_merge($projects, $result);
        }

        # Sort the list? Everyone wants watchers first!
        $sort_type = strtolower($sort_type);
        if($sort_type && is_callable(array(__CLASS__, "sortby$sort_type")))
        {
            $sorter = "sortby$sort_type";
            $projects = self::$sorter($projects, $sort_asc);
        }

        return $projects;
    }

    /**
     * Fetch project info from github
     * @param string $location
     * @param string $username
     * @return array[WPGH_Project] An array of projects
     */
    public static function fetch_github($username)
    {
        $projects = array();

        $url  = "https://api.github.com/users/$username/repos";
        $json = WPGH_Net::get($url);

        if(!is_array($json = json_decode($json)))
            return FALSE;

        foreach($json as $repo)
        {
            $proj = new WPGH_Project;
            $proj->url = $repo->html_url;
            $proj->name = $repo->name;
            $proj->description = $repo->description;
            $proj->watchers = $repo->watchers;
            $proj->source = "GitHub";
            $proj->watcher_noun = ($repo->watchers == 1 ? 'watcher' : 'watchers');
            $proj->updated = strtotime($repo->pushed_at);

            $projects[] = $proj;
        }

        return $projects;
    }

    /**
     * Fetch the projects from BitBucket
     * @param string $username
     * @return array An array of projects
     */
    public static function fetch_bitbucket($username)
    {
        $projects = array();

        $url  = "https://api.bitbucket.org/1.0/users/$username/?format=json";
        $json = WPGH_Net::get($url);

        if(!is_object($json = json_decode($json)))
            return FALSE;

        foreach($json->repositories as $repo)
        {
            $proj = new WPGH_Project;
            $proj->url = "https://bitbucket.org/$username/{$repo->slug}";
            $proj->name = $repo->name;
            $proj->description = $repo->description;
            $proj->watchers = $repo->followers_count;
            $proj->source = "BitBucket";
            $proj->watcher_noun = ($repo->followers_count == 1 ? 'watcher' : 'watchers');
            $proj->updated = 0;

            $projects[] = $proj;
        }

        return $projects;
    }

    /**
     * Fetch the projects from Sourceforge
     * @param string $username
     * @return array An array of projects
     */
    public static function fetch_sourceforge($username)
    {
        $projects = array();

        $url  = "http://sourceforge.net/api/user/username/$username/json";
        $json = WPGH_Net::get($url);

        if(!is_object($json = json_decode($json)))
            return FALSE;

        foreach($json->User->projects as $repo)
        {
            $proj = new WPGH_Project;
            $proj->url = "#";
            $proj->name = $repo->unix_name;
            $proj->description = $repo->name;
            $proj->watchers = 1; # The guy who wrote it.. consistent with github
            $proj->source = "SourceForge";
            $proj->watcher_noun = ($proj->watchers == 1 ? 'watcher' : 'watchers');
            $proj->updated = 0;

            $projects[] = $proj;
        }

        return $projects;
    }

    /**
     * Sort a list of projects by watchers using PHP's usort
     * @param array[WPGH_Project] $projects
     * @param bool $is_asc
     * @return array[WPGH_Project]
     */
    public static function sortbywatchers($projects, $is_asc = TRUE)
    {
        if($is_asc)
            usort($projects, array(__CLASS__, 'compareWatchersAsc'));
        else
            usort($projects, array(__CLASS__, 'compareWatchersDesc'));

        return $projects;
    }

    /**
     * Compare the watchers for ascending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareWatchersAsc($p1, $p2)
    {
        return $p1->watchers < $p2->watchers ? -1 : 1;
    }

    /**
     *
     * Compare the watchers for descending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareWatchersDesc($p1, $p2)
    {
        return $p1->watchers > $p2->watchers ? -1 : 1;
    }

    /**
     * Sort a list of projects by name using PHP's usort
     * @param array[WPGH_Project] $projects
     * @param bool $is_asc
     * @return array[WPGH_Project]
     */
    public static function sortbyname($projects, $is_asc = TRUE)
    {
        if($is_asc)
            usort($projects, array(__CLASS__, 'compareNamesAsc'));
        else
            usort($projects, array(__CLASS__, 'compareNamesDesc'));
        
        return $projects;
    }

    /**
     * Compare the names for ascending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareNamesAsc($p1, $p2)
    {
        return strtolower($p1->name) < strtolower($p2->name) ? -1 : 1;
    }

    /**
     *
     * Compare the names for descending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareNamesDesc($p1, $p2)
    {
        return strtolower($p1->name) > strtolower($p2->name) ? -1 : 1;
    }

    /**
     * Sort a list of projects by time last updated using PHP's usort
     * @param array[WPGH_Project] $projects
     * @param bool $is_asc
     * @return array[WPGH_Project]
     */
    public static function sortbyupdated($projects, $is_asc = TRUE)
    {
        if($is_asc)
            usort($projects, array(__CLASS__, 'compareUpdatedAsc'));
        else
            usort($projects, array(__CLASS__, 'compareUpdatedDesc'));
        
        return $projects;
    }

    /**
     * Compare the time last updated for ascending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareUpdatedAsc($p1, $p2)
    {
        return $p1->updated < $p2->updated ? -1 : 1;
    }

    /**
     *
     * Compare the time last updated for descending order
     * @param WPGH_Project $p1
     * @param WPGH_Project $p2
     * @return array
     */
    public static function compareUpdatedDesc($p1, $p2)
    {
        return $p1->updated > $p2->updated ? -1 : 1;
    }

}
