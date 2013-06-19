<?php
namespace lowtone\media\video\videos;
use lowtone\dom\Document,
	lowtone\wp\meta\Meta,
	lowtone\wp\attachments\Attachment;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2012, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\media\video\videos
 */
class Video extends Attachment {

	protected static $videoClasses = array();

	const PLAYER_OPTION_AUTOPLAY = "autoplay",
		PLAYER_OPTION_CONTROLS = "controls",
		PLAYER_OPTION_HEIGHT = "height",
		PLAYER_OPTION_LOOP = "loop",
		PLAYER_OPTION_MUTED = "muted",
		PLAYER_OPTION_POSTER = "poster",
		PLAYER_OPTION_PRELOAD = "preload",
		PLAYER_OPTION_WIDTH = "width";

	const META_ALT_SRC = "_lowtone_media_video_alt_src";
	
	public function image() {
		if (false === ($thumbnail = $this->getPostThumbnail()))
			return NULL;

		return $thumbnail->getAttachedFileUrl();
	}

	public function sources() {
		$uploadDir = wp_upload_dir();

		return array_merge(
				array(
					array(
						"src" => $this->getAttachedFileUrl(),
						"type" => $this->{self::PROPERTY_POST_MIME_TYPE},
					)
				), 
				array_map(function($meta) use ($uploadDir) {
					$source = (array) $meta->{Meta::PROPERTY_META_VALUE};

					if (isset($source["src"]))
						$source["src"] = $uploadDir["baseurl"] . "/" . $source["src"];

					return $source;
				}, (array) $this->getMeta()->findByKey(self::META_ALT_SRC))
			);
	}

	public function player($options = NULL) {

		$options = array_merge(array(
				self::PLAYER_OPTION_CONTROLS => 1,
				self::PLAYER_OPTION_POSTER => $this->image(),
			), (array) $options);

		$atts = array_intersect_key($options, array_flip(array(
				self::PLAYER_OPTION_AUTOPLAY,
				self::PLAYER_OPTION_CONTROLS,
				self::PLAYER_OPTION_HEIGHT,
				self::PLAYER_OPTION_LOOP,
				self::PLAYER_OPTION_MUTED,
				self::PLAYER_OPTION_POSTER,
				self::PLAYER_OPTION_PRELOAD,
				self::PLAYER_OPTION_WIDTH,
			)));

		$player = new Document();

		$videoElement = $player
			->createAppendElement("video")
			->setAttributes($atts);

		foreach ($this->sources() as $source) {

			$videoElement
				->createAppendElement("source")
				->setAttributes($source);

		}

		$videoElement->appendChild($player->createTextNode(__("Your browser does not support video playback.", "lowtone_media_video")));

		return $player->saveHtml();
	}

	// Static
	
	public static function __postType() {
		return Attachment::__postType();
	}

	public static function __register(array $options = NULL) {
		add_action("add_meta_boxes", function($postType, $post) {
			if (!preg_match("#^video/#i", $post->{Video::PROPERTY_POST_MIME_TYPE}))
				return;

			add_action("edit_form_after_title", function() use ($post) {
				$video = Video::fromPost($post);

				echo '<div class="wp_attachment_holder">';

				echo $video->player(array(
						"width" => "100%",
					));

				echo '</div>';
			}, 0);

			if (current_theme_supports("post-thumbnails")) {

				wp_enqueue_media();

				add_meta_box(
						"postimagediv", 
						__("Poster", "lowtone_media_video"), 
						"post_thumbnail_meta_box", 
						Video::__postType(), 
						"side"
					);

			}

		}, 10, 2);

		return get_post_type_object(self::__postType());
	}

	public static function create($properties = NULL, array $options = NULL) {
		$properties = (array) $properties;

		if (isset($properties[self::PROPERTY_POST_MIME_TYPE]))
			$options[self::OPTION_CLASS] = self::__videoClass($properties[self::PROPERTY_POST_MIME_TYPE]);

		return parent::create($properties, $options);
	}

	public static function fromPost($post) {
		$class = self::__videoClass($post->{Video::PROPERTY_POST_MIME_TYPE});

		if (!class_exists($class))
			throw new \ExceptionError("Video class not found");

		return new $class($post);
	}

	public static function __videoClass($mimeType) {
		foreach (self::$videoClasses as $t => $class) {
			if ($t != $mimeType)
				continue;

			return $class;
		}

		return get_called_class();
	}

	public static function __registerVideoClass($mimeType, $class = NULL) {
		if (!isset($class))
			$class = get_called_class();

		foreach ((array) $mimeType as $m)
			self::$videoClasses[$m] = $class;

		return self::$videoClasses;
	}

}