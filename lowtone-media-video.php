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
		lowtone\media\video\videos\Video,
		lowtone\wp\WordPress;

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

				// Attachment image src

				$__disableGetAttachmentImageSrc = false;

				add_filter("get_attachment_image_src", function($src, $attachmentId, $size, $icon) use (&$__disableGetAttachmentImageSrc) {
					if ($__disableGetAttachmentImageSrc)
						return $src;

					$__disableGetAttachmentImageSrc = true;

					if ($thumbnailId = get_post_thumbnail_id($attachmentId)) 
						$src = wp_get_attachment_image_src($thumbnailId, $size, $icon);

					$__disableGetAttachmentImageSrc = false;
					
					return $src;
				}, 20, 4);

				// Extend media editor
				
				add_action("wp_enqueue_media", function() {
					wp_enqueue_script("lowtone_media_video_extend_featuredimage", plugins_url("/assets/scripts/extend_featuredimage.js", __FILE__), array("media-views"), false, true);
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