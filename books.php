<?php

$books = json_decode(file_get_contents("books.json"), true);

$authors = [];

foreach($books as $book) {
    if(!isset($authors[$book['author']])) {

        preg_match('/\d{2,4}/', $book['author'], $matches);
        $expired = count($matches) ? $matches[0] : null;

        $authors[$book['author']] = [
            'id' => count($authors),
            'name' => $book['author'],
            'expired' => $expired,
            'synonyms' => [
                $book['author'],
            ],
        ];
    }
}

uasort($authors, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

$authors = array_values($authors);
$authorsFile = 'found-authors.json';
file_put_contents($authorsFile, json_encode($authors, JSON_PRETTY_PRINT));