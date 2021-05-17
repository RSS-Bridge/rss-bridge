<?php

class SimpleLoginBridge extends BridgeAbstract {
	const NAME = 'SimpleLogin Blog';
	const URI = 'https://simplelogin.io';
	const DESCRIPTION = 'Gets the latest SimpleLogin blog posts';
	const MAINTAINER = 'tblyler';
	const CACHE_TIMEOUT = 3600; // 1 hour
	const BLOG_URI = 'https://simplelogin.io/blog';

	public function collectData() {
		$html = getSimpleHTMLDOMCached(self::BLOG_URI, self::CACHE_TIMEOUT)
			or returnServerError('Unable to load posts from "' . self::BLOG_URI . '"!');

		// loop through each blog post div
		foreach ($html->find('div.mb-5') as $post) {
			// get the children for this post
			$post_children = $post->children();

			// recursively loop through all child elements and fix HREF locations
			// that are relative to this domain
			$fix_href_children = $post_children;
			while ($fix_href_children) {
				$child = array_pop($fix_href_children);
				$children = $child->children();
				if ($children) {
					$fix_href_children += $children;
				}

				// if the HREF starts with a /, prepend the URI to make a valid link
				if (isset($child->href) && $child->href[0] == '/') {
					$child->href = self::URI . $child->href;
				}
			}

			$this->items[] = array(
				'uri'       => $post_children[0]->href,
				'title'     => $post_children[0]->children(0)->innertext,
				'content'   => $post_children[1]->innertext,
				'timestamp' => trim(strstr($post_children[2]->innertext, ' -', true)),
			);
		}
	}
}
