<?php
require_once "Michelf/Markdown.php";
require_once "Michelf/MarkdownExtra.php";

use \Michelf\MarkdownExtra;

class MarkdownHelper {
	/**
	 * Parses markdown (and some extras):
	 *		Markdown
	 *		Autodetect links and images
	 *
	 * @param $text: String to parse
	 * @param $options: optional associative array with options:
	 *		'markdown': bool, turn on markdown parsing	Default: true
	 *		'link_detection': bool, turn on link autodetection Default: true
	 *		'image_detection': bool, turn on image autodetection Default: true ( syntax: !!url)
	 *		'strip_images': bool, turn images to links	Default: false
	 *		'allow_html': bool, set to true to not strip html, Default: false
	 *		'nl2br': bool true to run nl2br in paragraphs in markdown: Default: true
	 *
	 *	@return The html output
	 */
	public static function parse($text, $options=array()) {
		$strip_images = static::option($options,'strip_images');

		//parse markdown
		if(static::option($options,'markdown')) {
			$markdown = new MarkdownExtra();

			$markdown->no_markup = !static::option($options, 'allow_html');
			$markdown->nl2br = static::option($options, 'nl2br');

			$text = $markdown->transform($text);

			if($strip_images) {
				$text = preg_replace("#<img .*?src=\"(.+?)\".*?/>#i", "$1",$text);
			}
		} elseif(!static::option($options,'allow_html')) {
			// Markdown makes entities for us - we only need this if markdown option is OFF
			$text = htmlentities($text, ENT_QUOTES,'UTF-8' );
		}

		$detect_links = static::option($options, 'link_detection');
		$detect_images = static::option($options, 'image_detection') && !$strip_images;

		$text = static::parse_urls($text, $detect_links, $detect_images);

		return $text;
	}

	private static function parse_urls($text, $detect_links, $detect_images) {
		if($detect_links || $detect_images) {
			//$text = preg_replace("#(?<=[[:blank:]:.])(https?://|www).+?(?=[\\b]? )#i", "[[$0]]",$text);
			$text = preg_replace_callback("#(?<=(?:[[:blank:]:.>]|\n))((?:!!)?)((?:https?://|www\.).+?)(?=[.:,;?]?(?: |<|\n))#i", function($matches) use($detect_links, $detect_images){
				$image = ($matches[1] == "!!");
				$url = $matches[2];
				if(preg_match("#https?://#i", $url) == 0) {
					$url = "http://$url";
				}
				if($image && $detect_images) {
					return "<img src='$url'/>";
				} elseif ($detect_links) {
					return "<a href='$url'>{$matches[2]}</a>";
				} else {
					return $matches[0];
				}
			},$text);
		}
		return $text;
	}

	private static function option($option_array, $option) {
		$default_values = array(
			'markdown' => true,
			'link_detection' => true,
			'image_detection' => true,
			'strip_images' =>false,
			'allow_html' => false,
			'nl2br' => true
		);

		if(!isset($option_array[$option]))
			return $default_values[$option];
		else
			return $option_array[$option];
	}
}
