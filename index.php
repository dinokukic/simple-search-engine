<?php 

$start = "http://localhost:8888/simple-search-engine/test.html";

$already_crawled = array();

$crawling = array();

function get_details($url) {

	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: linkoutBot/1.0\n"));

	$context = stream_context_create($options);

	$doc = new DOMDocument();
	@$doc -> loadHTML(@file_get_contents($url, false, $context));

	$title = $doc->getElementsByTagName("title");
	$title = $title->item(0)->nodeValue;

	$description = "";
	$keywords = "";
	$metas = $doc->getElementsByTagName("meta");
	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas -> item($i);
		if ($meta->getAttribute("name") == strtolower("description")) {
			$description = $meta->getAttribute("content");
		}
		if ($meta->getAttribute("name") == strtolower("keywords")) {
			$keywords = $meta->getAttribute("content");
		}
	}
	return '{ "Title": "' . str_replace("\n", "", $title) . '", "Description": "' . str_replace("\n", "", $description) . '", "Keywords":"'. str_replace("\n", "", $keywords) .'", "URL:" "' . $url . '"},';
}


function follow_links($url) {

	global $already_crawled;
	global $crawling;

	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: linkoutBot/1.0\n"));

	$context = stream_context_create($options);

	$doc = new DOMDocument();
	@$doc -> loadHTML(@file_get_contents($url, false, $context));

	$linklist = $doc -> getElementsByTagName("a");

	foreach ($linklist as $link) {
		$l = $link -> getAttribute("href");

		if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
			$l = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . $l;
		} else if (substr($l, 0, 2) == "//") {
			$l = parse_url($url, PHP_URL_SCHEME) . ":" . $l;
		} else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . dirname(parse_url($url, PHP_URL_PATH)) . substr($l, 1);
		} else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH) . $l;
		} else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . "/" . $l;
		} else if (substr($l, 0, 11) == "javascript:") {
			continue;
		} else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . "/" . $l;
		}

		if (!in_array($l, $already_crawled)) {
			$already_crawled[] = $l;
			$crawling[] = $l;
			echo get_details($l)."\n";
			//echo $l."\n";

		}
		
	}

	array_shift($crawling);
	foreach ($crawling as $site) {
		follow_links($site);
	}

}

follow_links($start);

print_r($already_crawled);
?>