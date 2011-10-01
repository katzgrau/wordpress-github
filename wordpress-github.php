<?php
/*
Plugin Name: GitHub & BitBucket Project Lister
Plugin URI: https://github.com/katzgrau/wordpress-github
Description: List your github and bitbucket projects on your Wordpress blog really, really easily. Why? Because you're a baller.
Version: 1.0.6
Author: Kenny Katzgrau
Author URI: http://codefury.net
*/

require_once dirname(__FILE__) . '/lib/Project.php';
require_once dirname(__FILE__) . '/lib/Utility.php';
require_once dirname(__FILE__) . '/lib/View.php';

add_filter("the_content",  array('WPGH_Core', 'insertProjects'));
add_action('admin_menu',   array('WPGH_Core', 'registerAdmin'));
add_action('widgets_init', array('WPGH_Core', 'registerWidget'));

/**
 * This class is the core of the github/bitbucket project lister.
 */
class WPGH_Core
{

    /**
     * The amount of time to cache the projects for
     * @var int
     */
    public static $_cacheExpiration = 3600;


    /**
     * A callback that will parse out things in the form {{source:username}} and
     *  replace it with the project list. Ex, {{github:katzgrau}}
     * @param string $content
     * @return string The updated content
     */
    static function insertProjects($content)
    {
        return preg_replace_callback('/\{\{((?:[\w\d\-_]+\:[\w\d\-_]+(?:[\,\s]+)?)+)\}\}/', array(__CLASS__, 'replaceCallback'), $content);
    }

    /**
     * The preg_replace callback used for self::insertProjects()
     * @param array $matches The matches array
     * @return string
     */
    static function replaceCallback($matches)
    {
        $projects = WPGH_Project::fetch($matches[1]);

        $output   = self::getOpeningListTemplate();

        foreach($projects as $project)
        {
            $template = self::getProjectTemplate();
            $template = str_replace('{{PROJECT_URL}}', $project->url, $template);
            $template = str_replace('{{PROJECT_NAME}}', htmlentities($project->name), $template);
            $template = str_replace('{{PROJECT_WATCHER_COUNT}}', $project->watchers, $template);
            $template = str_replace('{{PROJECT_DESCRIPTION}}', htmlentities($project->description), $template);
            $template = str_replace('{{PROJECT_SOURCE}}', $project->source, $template);
            $template = str_replace('{{PROJECT_WATCHER_NOUN}}', $project->watcher_noun, $template);

            $output .= $template . "\n";
        }

        return $output . self::getClosingListTemplate();
    }

    /**
     * Get the template used to output the project list
     * @return string
     */
    static function getProjectTemplate()
    {
$template = <<<TEMP
<li>
    <h4>
        <a href="{{PROJECT_URL}}">
            {{PROJECT_NAME}}
        </a>
    </h4>
    <p>{{PROJECT_DESCRIPTION}} <small>({{PROJECT_WATCHER_COUNT}} {{PROJECT_WATCHER_NOUN}})</small></p>
</li>
TEMP;


        return WPGH_Utility::getOption('wpgh_template', $template);
    }

    /**
     * Get any text we need to output before the list (perhaps, ul)
     * @return string
     */
    static function getOpeningListTemplate()
    {
        return WPGH_Utility::getOption('wpgh_opener', '<ul>');
    }

    /**
     * Get any text we need to output before the list (perhaps, ul)
     * @return string
     */
    static function getClosingListTemplate()
    {
        return WPGH_Utility::getOption('wpgh_closer', '</ul>');
    }

    /**
     * Register the admin settings page
     */
    static function registerAdmin()
    {
        add_options_page('Github/BitBucket', 'GitHub/BitBucket', 'edit_pages', 'wordpress-github.php', array(__CLASS__, 'adminMenuCallback'));
    }

    /**
     * The function used by WP to print the admin settings page
     */
    static function adminMenuCallback()
    {
        $submit  = WPGH_Utility::arrayGet($_POST, 'wpgh_submit');
        $updated = FALSE;

        if($submit)
        {
            WPGH_Utility::setOption('wpgh_opener',   WPGH_Utility::arrayGet($_POST, 'wpgh_opener'));
            WPGH_Utility::setOption('wpgh_template', WPGH_Utility::arrayGet($_POST, 'wpgh_template'));
            WPGH_Utility::setOption('wpgh_closer',   WPGH_Utility::arrayGet($_POST, 'wpgh_closer'));

            $updated = TRUE;
        }

        $data = array (
            'wpgh_opener'   => self::getOpeningListTemplate(),
            'wpgh_closer'   => self::getClosingListTemplate(),
            'wpgh_template' => self::getProjectTemplate(),
            'wpgh_updated'  => $updated
        );

        WPGH_View::load('admin', $data);
    }

    /**
     * The callback used to register the widget
     */
    static function registerWidget()
    {
        register_widget('WPGH_Widget');
    }
}


/**
 * This is an optional widget to display GitHub projects
 */
class WPGH_Widget extends WP_Widget
{
    /**
     * Set the widget options
     */
     function WPGH_Widget()
     {
        $widget_ops = array('classname' => 'wpgh_projects', 'description' => 'A list of your GitHub or BitBucket projects');
        $this->WP_Widget('wpgh_projects', 'GitHub Projects', $widget_ops);
     }

     /**
      * Display the widget on the sidebar
      * @param array $args
      * @param array $instance
      */
     function widget($args, $instance)
     {
         extract($args);
         $title       = apply_filters('widget_title', $instance['w_title']);
         $info_string = $instance['w_info_string'];
         $w_opener    = $instance['w_opener'];
         $w_closer    = $instance['w_closer'];

         echo $before_widget;

         if($title);
            echo $before_title . $title. $after_title;

         echo $w_opener;
         
         $projects = WPGH_Project::fetch($info_string);

         if(count($projects) > 0)
         {
             echo "<ul>";
             foreach($projects as $project)
             {
                $noun = $project->watchers == 1 ? 'watcher' : 'watchers';
                echo "<li>";
                    echo "<a target=\"_blank\" href=\"{$project->url}\" title=\"{$project->description} &mdash; {$project->watchers} $noun \">";
                            echo $project->name;
                    echo "</a>";
                echo "</li>";
             }
             echo "</ul>";
         }

         echo $w_closer;

         echo $after_widget;
     }

     /**
      * Update the widget info from the admin panel
      * @param array $new_instance
      * @param array $old_instance
      * @return array
      */
     function update($new_instance, $old_instance)
     {
        $instance = $old_instance;
        
        $instance['w_title']       = $new_instance['w_title'];
        $instance['w_info_string'] = $new_instance['w_info_string'];
        $instance['w_opener']      = $new_instance['w_opener'];
        $instance['w_closer']      = $new_instance['w_closer'];

        return $instance;
     }

     /**
      * Display the widget update form
      * @param array $instance
      */
     function form($instance) 
     {

        $defaults = array('w_title' => 'GitHub Projects', 'w_info_string' => '', 'w_opener' => '', 'w_closer' => '');
		    $instance = wp_parse_args((array) $instance, $defaults);
       ?>
        <div class="widget-content">
       <p>
            <label for="<?php echo $this->get_field_id('w_title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('w_title'); ?>" name="<?php echo $this->get_field_name('w_title'); ?>" value="<?php  echo $instance['w_title']; ?>" />
       </p>
       <p>
            <label for="<?php echo $this->get_field_id('w_info_string'); ?>">Sources:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'w_info_string' ); ?>" name="<?php echo $this->get_field_name('w_info_string'); ?>" value="<?php echo $instance['w_info_string']; ?>" />
            <small>eg, github:katzgrau</small>
       </p>
       <div style="border-bottom: 1px dotted #ccc; margin-bottom: 8px; margin-left: 10px; margin-right: 10px;"></div>
       <p>
            <label for="<?php echo $this->get_field_id('w_opener'); ?>">Pre-List Markup (HTML):</label>
            <textarea class="widefat" id="<?php echo $this->get_field_id( 'w_opener' ); ?>" name="<?php echo $this->get_field_name('w_opener'); ?>"><?php echo $instance['w_opener']; ?></textarea>
            <small>Optional</small>
       </p>
       <p>
            <label for="<?php echo $this->get_field_id('w_closer'); ?>">Post-List Markup (HTML):</label>
            <textarea class="widefat" id="<?php echo $this->get_field_id( 'w_closer' ); ?>" name="<?php echo $this->get_field_name('w_closer'); ?>"><?php echo $instance['w_closer']; ?></textarea>
            <small>Optional</small>
       </p>
        </div>
       <?php
     }
}




