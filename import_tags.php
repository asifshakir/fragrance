<?php

// Download all wordpress tags using the wordpress API'
// and save them in a file called 'tags.json'
$file = "tags.json";

// fetch all tags iteratively
$tags = [];
$page = 1;
$totalPages = 1;
$fetchedHeaders = false;
$ctr = 1;

while ($page <= $totalPages) {
    echo "Fetching page $page<br />";
    $url = "https://thestrongrope.com/wp-json/wp/v2/tags?page=$page";

    // get the headers
    if(!$fetchedHeaders){
        $headers = get_headers($url, 1);
        $totalPages = $headers['x-wp-totalpages'];
        $fetchedHeaders = true;
    }
    
    $contents = file_get_contents($url);
    $new_tags = json_decode($contents, true);

    if (count($new_tags) == 0) {
        break;
    }

    foreach($new_tags as $t) {
        echo $ctr . ". " . $t['name'] . "<br />";
    }

    $tags = array_merge($tags, $new_tags);

    $page++;
    $ctr++;
    ob_flush();
}

$contents = json_encode($tags);
file_put_contents($file, $contents);