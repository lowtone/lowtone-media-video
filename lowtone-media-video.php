<?php
/*
 * Plugin Name: Video
 * Plugin URI: http://wordpress.lowtone.nl/media-video
 * Description: Better support for video.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2012, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\media\video
 */

namespace lowtone\media\video {

	use lowtone\content\packages\Package,
		lowtone\media\video\videos\Video;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\wp", "lowtone\\media"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				Video::__register();
				
				add_shortcode("video", function($atts, $content) {
					if (isset($atts["id"])) {
						$video = Video::findById($atts["id"]);
					} else if ($content) {
						$video = new Video();
					}

					if (!isset($video))
						return '<!-- Invalid video resource -->';

					return $video->player($atts);
				});

				add_filter("video_send_to_editor_url", function($html, $src, $title) {
					return '[video]' . $src . '[/video]';
				}, 10, 3);

				add_filter("media_send_to_editor", function($html, $id, $attachment) {
					return sprintf('[video id=%s /]', $id);
				}, 10, 3);

				// Register textdomain
				
				add_action("plugins_loaded", function() {
					load_plugin_textdomain("lowtone_media_video", false, basename(__DIR__) . "/assets/languages");
				});

				return true;
			}
		));

	if (!$__i)
		return false;

}