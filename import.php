<?php

// Download all wordpress articles using the wordpress API'
// and save them in a file called 'articles.json'

$url = "https://thestrongrope.com/wp-json/wp/v2/posts";


// fetch all posts iteratively
$posts = [];
$page = 1;
$totalPages = 1;
$fetchedHeaders = false;
$ctr = 1;

while ($page <= $totalPages) {
    echo "Fetching page $page<br />";
    $url = "https://thestrongrope.com/wp-json/wp/v2/posts?page=$page";

    // get the headers
    if(!$fetchedHeaders){
        $headers = get_headers($url, 1);
        $totalPages = $headers['x-wp-totalpages'];
        $fetchedHeaders = true;
    }
    
    $contents = file_get_contents($url);
    $new_posts = json_decode($contents, true);

    if (count($new_posts) == 0) {
        break;
    }

    foreach($new_posts as $p) {
        echo $ctr . ". " . $p['title']['rendered'] . "<br />";
    }

    $posts = array_merge($posts, $new_posts);

    $page++;
    $ctr++;
    ob_flush();
}

$contents = json_encode($posts);
$filepath = "articles.json";
file_put_contents($filepath, $contents);