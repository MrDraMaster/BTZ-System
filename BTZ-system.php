<?php

/*
Plugin Name: BTZ System
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: Paul Louis Gerlach
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

/**
 * Class BTZ_System
 *
 */
final class BTZ_System {

    public function __construct() {
        foreach ( glob(plugin_dir_path(__FILE__)."PHP/*.php" ) as $file){
            require_once( $file );
        }
    }
}