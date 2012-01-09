<?php

	// Assign these variables.
	$consumerKey = '';
	$consumerSecret = '';
	$OAuthToken = '';
	$OAuthTokenSecret = '';

	define('SAFE', TRUE);

	include('lib.twitter.php');
	$t = new Twitter($consumerKey,$consumerSecret);
	$t->setOAuthToken($OAuthToken);
	$t->setOAuthTokenSecret($OAuthTokenSecret);

	$tags = '#anime #torrent';

	$series = array(
		array('name' => 'Naruto: Shippuden', 'tags' => '#Taka', 'prefix' => '[Taka]_Naruto_Shippuuden_', 'feed' => 'http://www.nyaa.eu/?page=rss&term=naruto+shippuuden+taka+720p&filter=2'),
		array('name' => 'Bleach', 'tags' => '#HorribleSubs', 'prefix' => '[HorribleSubs] Bleach - ', 'feed' => 'http://www.nyaa.eu/?page=rss&term=bleach+720p&filter=2'),
		array('name' => 'Gintama', 'tags' => '#HorribleSubs #MKV',  'prefix' => '[HorribleSubs] Gintama - ', 'feed' => 'http://www.nyaa.eu/?page=rss&term=Gintama+720p+HorribleSubs&filter=2'),
		array('name' => 'Gosick', 'tags' => '#HatsuyukiTsuki #MKV',  'prefix' => '[Hatsuyuki-Tsuki]_Gosick_-_', 'feed' => 'http://www.nyaa.eu/?page=rss&term=Gosick+Hatsuyuki-Tsuki+720&filter=2'),
	);

	header('Content-Type: text/plain');

	$new = 0;

	foreach($series as $anime) {
		$feed = file_get_contents($anime['feed']);
		if(!$feed) continue;

		$feed = simplexml_load_string($feed);
		$seriesTweeted = false;

		foreach($feed->channel->item as $release) {
			if(substr($release->title, 0, strlen($anime['prefix'])) == $anime['prefix']) {
				$episode = substr($release->title, strlen($anime['prefix']));
				@preg_match('/[0-9]{1,4}/', $episode, $episode);
				if(!isset($episode[0]) || !strlen($episode[0])) continue;
				$episode = (int)$episode[0];

				$cache = $episode . '_' . md5($release->guid);
				if(@file_exists("./cache/{$cache}.txt")) continue;

				$download = str_replace('&amp;', '&', $release->link);
				$download = Shortlink($download);

				$tweet = trim("{$anime['name']} #{$episode} - {$download} {$anime['tags']} {$tags}");
				if(strlen($tweet) > 130) $tweet = trim("{$anime['name']} #{$episode} - {$download} {$anime['tags']}");
				if(strlen($tweet) > 130) $tweet = trim("{$anime['name']} #{$episode} - {$download}");

				if($seriesTweeted) {
					@file_put_contents("./cache/{$cache}.txt", '');
					$new++;
				} else {
					try {
						$sent = $t->statusesUpdate($tweet);
						$seriesTweeted = true;
						if(isset($sent['id'])) {
							@file_put_contents("./cache/{$cache}.txt", '');
							$new++;
						}
					} catch (Exception $e) {
						// Do something here I suppose.
					}
				}

			}
		}
	}

	if($new) {
		$t->directMessagesNew("Added {$new} releases.", 'evansims');
	}

	echo "Done. {$new} new releases.";
	exit;

	function Shortlink($url) {
		$tinyurl = file_get_contents('http://tinyurl.com/api-create.php?url=' . $url);
		if(strlen($tinyurl) > 0 && strlen($tinyurl) < 32) return $tinyurl;
		return $url;
	}
