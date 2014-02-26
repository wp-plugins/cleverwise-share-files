<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

//	if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$fs_wp_option='share_files';
$fs_wp_option_version_txt=$fs_wp_option.'_version';
$fs_wp_option_section_cats_txt=$fs_wp_option.'_section_cats';

global $wpdb;

//	For Single site
if (!is_multisite()) {
    delete_option($fs_wp_option);
    delete_option($fs_wp_option_version_txt);
    delete_option($fs_wp_option_section_cats_txt);

//	For Multisite
} else {
    $blog_ids=$wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    $original_blog_id=get_current_blog_id();
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        delete_site_option($fs_wp_option);
        delete_site_option($fs_wp_option_version_txt);
        delete_site_option($fs_wp_option_section_cats_txt);
    }
    switch_to_blog($original_blog_id);
}

$wp_db_prefix=$wpdb->prefix;
$cw_share_files_cats_tbl=$wp_db_prefix.'share_files_cats';
$cw_share_files_dls_tbl=$wp_db_prefix.'share_files_dls';

$wpdb->query("DROP TABLE IF EXISTS $cw_share_files_cats_tbl");
$wpdb->query("DROP TABLE IF EXISTS $cw_share_files_dls_tbl");
