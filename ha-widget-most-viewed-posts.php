<?php
/**
 * Plugin Name: Widget - gefilterte meist aufgerufene Beiträge
 * Description: Widget für meist aufgerufene Beiträge mit Filter für berechtigte Beiträge. Erfordert das Plugin "Content Control Addons" von Alexander Hacker, sowie "Page Visit Counter"
 * Version: 0.1
 * Author: Alexander Hacker
 * Author URI: https://edv-hacker.de
 * Text Domain: widget-most-viewed-posts
 *
 * Minimum PHP: 5.3
 * Minimum WP: 3.5
 *
 * @author      Alexander Hacker
 * @copyright   Copyright (c) 2020, Alexander Hacker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize Plugin Update Checker for automatic updates from GitHub repository.
 *
 * @return \YahnisElsts\PluginUpdateChecker\v5\Vcs\PluginUpdateChecker|null
 */
function ha_widget_most_viewed_posts_get_updater() {
    static $update_checker = null;
    if ( null !== $update_checker ) {
        return $update_checker;
    }

    $puc_file = plugin_dir_path( __FILE__ ) . 'vendor/plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists( $puc_file ) ) {
        return null;
    }

    require_once $puc_file;

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/mystryx1337/ha-widget-most-viewed-posts/',
        __FILE__,
        'ha-widget-most-viewed-posts'
    );

    // Set default branch to main
    $update_checker->setBranch( 'main' );

    // Set GitHub Access Token (if defined/available)
    $token = defined( 'HA_GITHUB_UPDATE_TOKEN' ) ? HA_GITHUB_UPDATE_TOKEN : '';
    if ( empty( $token ) && defined( 'GITHUB_ACCESS_TOKEN' ) ) {
        $token = GITHUB_ACCESS_TOKEN;
    }
    $token = apply_filters( 'ha_github_update_token', $token, 'ha-widget-most-viewed-posts' );

    if ( ! empty( $token ) ) {
        $update_checker->setAuthentication( $token );
    }

    return $update_checker;
}
ha_widget_most_viewed_posts_get_updater();



class Most_Viewed_Posts_Filtered_Widget extends WP_Widget{
    public function __construct(){
		parent::__construct(
            'most_viewed_posts_filtered_widget',
            'Most Viewed Posts Filtered Widget',
            array(
                'description' => 'Meistbesuchte Einträge (nach Rechten gefiltert) anzeigen'
            )
        );
    }
	
	//Admin Form
    public function form($instance){
		$defaults = [
            'title' => '',
        ];
        $instance = wp_parse_args((array)$instance, $defaults);

        ?>
        <p>
            <label for="<?=$this->get_field_id('title'); ?>">Titel:</label>
            <input class="widefat" id="<?=$this->get_field_id('title');?>" name="<?=$this->get_field_name('title');?>" type="text" value="<?=esc_attr($instance['title']);?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance){
        return [
            'title' => strip_tags($new_instance['title']),
        ];
    }

    public function widget($args, $instance){
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;

        if(!empty($title)){
            echo $before_title . $title . $after_title;
        }

        global $wpdb;
		
		$count_visit_limit = '8';
		$qry = "SELECT page_id, COUNT(page_id) AS counted FROM {$wpdb->prefix}page_visit_history GROUP BY page_id ORDER BY counted DESC";
		#$qry = "SELECT distinct page_id FROM {$wpdb->prefix}page_visit_history ORDER BY id DESC LIMIT 100";
        $count_visit = $wpdb->get_results($qry); //db call ok; no-cache ok

		//echo $qry;

		try {
			
			if($count_visit){
				
				$output = '<nav role="navigation" aria-label="'.$title.'"><ul>';

				$output_count = 0;
				foreach($count_visit as $visit_page){
					if(!function_exists('is_readable_post') || is_readable_post($visit_page->page_id)){
						$post = get_post($visit_page->page_id);
						if($post){
							$output .= '<li>';
							$output .= '<a href="'.get_permalink($visit_page->page_id).'" title="'.$post->post_title.'">';
							$output .= $post->post_title.'</a>';
							#$output .= ' <span class="post-date">'.date_i18n(get_option( 'date_format' ), strtotime($post->post_date)).'</span>';
							$output .= ' <span class="post-date">'.$visit_page->counted.'</span>';
							$output .= '</li>';
						
							$output_count++;
						}
					}
					
					if($output_count >= $count_visit_limit){
						break;
					}
				}
				
				$output .= '</ul></nav>';
				
				echo $output;
			}
		} catch (Exception $e) {
			echo "Abhängigkeiten fehlen";
		}
		
        echo $after_widget;
    }
}

add_action('widgets_init', function(){ return register_widget('Most_Viewed_Posts_Filtered_Widget'); });
