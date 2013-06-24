<?php
/*
 * Plugin Name: Video
 * Plugin URI: http://wordpress.lowtone.nl/media-video
 * Description: Imrpoved support for video.
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
					if (isset($atts["id"])) 
						$video = Video::findById($atts["id"]);
					else if ($content) 
						$video = new Video();

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

				// Return poster
				
				$__disableDownsize = false;
				
				add_filter("image_downsize", function($image, $postId, $size) use (&$__disableDownsize) {
					if ($__disableDownsize)
						return $image;

					$post = get_post($postId);

					if (!preg_match("#^video\/#", $post->{Video::PROPERTY_POST_MIME_TYPE}))
						return $image;

					if (!($thumbnailId = get_post_thumbnail_id($postId)))
						return $image;

					$__disableDownsize = true;

					$ret = image_downsize($thumbnailId, $size);

					$__disableDownsize = false;

					return $ret;
				}, 0, 3);

				// Post thumbnail
				
				$__disablePostThumbnail = false;
				
				add_filter("post_thumbnail_html", function($html, $postId, $thumbnailId, $size, $attr) use (&$__disablePostThumbnail) {
					if ($__disablePostThumbnail)
						return $html;
					
					$thumbnail = get_post($thumbnailId);

					if (!preg_match("#^video\/#", $thumbnail->{Video::PROPERTY_POST_MIME_TYPE}))
						return $html;

					if (is_singular() && ($video = Video::fromPost($thumbnail)))
						return $video->player();

					$__disablePostThumbnail = true;

					$html = get_the_post_thumbnail($thumbnailId, $size, $attr);

					$__disablePostThumbnail = false;

					return $html;
				});

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