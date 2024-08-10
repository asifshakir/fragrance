<?php

$articles = json_decode(file_get_contents("articles.json"), true);
$tags = json_decode(file_get_contents("tags.json"), true);

$tagNamesById = [];
foreach ($tags as $tag) {
    $tagNamesById[$tag['id']] = $tag['name'];
}

$structuredArticles = [];

$bookMasterFile = 'books.json';
$booksMaster = json_decode(file_get_contents($bookMasterFile), true);

$books = [];

// Display the article titles
foreach ($articles as $article) {
    $title = $article['title']['rendered'];
    $articleTags = $article['tags'];
    $numericTag = get_numeric_tag($tagNamesById, $articleTags);
    if ($numericTag) {

        $text = $article['content']['rendered'];

        // remove bookmenu div
        $text = preg_replace('/<div class=["]*?bookmenu["]*?>.*?<\/div>/s', '', $text);

        // remove all HTML comments
        $text = preg_replace('/<!--(.*?)-->/s', '', $text);

        // extract arabic text
        $arabicRegEx = '/<p class=["]*?ar["]*?>(.*?)<\/p>/s';
        $found = preg_match($arabicRegEx, $text, $matches);
        if (!$found) {
            echo '<h1>Not found</h1>';
            continue;
        }
        $arabic = $matches[0];
        $arabic = str_replace("\r", ' ', $arabic);
        $arabic = str_replace("\n", ' ', $arabic);
        $arabic = preg_replace('/\s+/', ' ', $arabic);
        $arabic = preg_replace('/<p class=["]*?ar["]*?>/s', '', $arabic);
        $arabic = preg_replace('/<\/p>/s', '', $arabic);

        $text = preg_replace($arabicRegEx, '', $text);

        $lines = str_replace("\r", '', $text);
        
        $lines = explode("\n", $text);
        
        $englishArr = [];
        $references = [];
        $notes = [];
        $englishStarted = true;
        $referencesStarted = false;
        $notesStarted = false;
        foreach($lines as $idx => $line) {
            $line = trim($line);
            if (empty($line)) {
                unset($lines[$idx]);
            } else if($line == '<h2 class="wp-block-heading">References</h2>'
                || $line == '<h2 class="wp-block-heading">References:</h2>'
                || $line == '<h2 class="wp-block-heading">References: </h2>'
                || $line == '<h2 class="wp-block-heading">References:&nbsp;</h2>'
                || $line == '<h2 class="wp-block-heading">Reference:</h2>'
                || $line == '<h2>References:</h2>') {
                $englishStarted = false;
                $notesStarted = false;
                $referencesStarted = true;
            } else if($line == "<h2 class=\"wp-block-heading\">Notes:</h2>"
                || $line == "<h2 class=\"wp-block-heading\">Notes: </h2>"
                || $line == "<h2 class=\"wp-block-heading\">Note:</h2>"
                || $line == '<h2 class="wp-block-heading">Note:</h2>'
                || $line == '<h2 class="wp-block-heading">Note: </h2>') {
                $englishStarted = false;
                $referencesStarted = false;
                $notesStarted = true;
            } else if($englishStarted) {
                $englishArr[] = $line;
                unset($lines[$idx]);
            } else if($referencesStarted) {
                $references[] = $line;
                unset($lines[$idx]);
            } else if($notesStarted) {
                $notes[] = $line;
                unset($lines[$idx]);
            }
        }

        $english = implode(" ", $englishArr);
        $english = preg_replace("/\s+/", " ", $english);
        $englishRegExp = '/<p.*?>(.*?)<\/p>/s';
        $found = preg_match_all($englishRegExp, $english, $matches);
        if ($found) {
            $english = $matches[1];
        } else {
            if(count((array) $english) == 0) {
                echo "<xmp>{$text}</xmp>";
                die('English not found');
            }

            if(!is_array($english)) {
                $english = [$english];
            }
        }
        $english = implode("\n", $english);

        $references = implode(" ", $references);
        $references = preg_replace("/\s+/", " ", $references);
        // extract all p tags
        $referenceRegExp = '/<p>(.*?)<\/p>/s';
        $found = preg_match_all($referenceRegExp, $references, $matches);
        if ($found) {
            $references = $matches[1];
        } else {
            if(count((array) $references) == 0) {
                echo "<xmp>{$text}</xmp>";
                die('References not found');
            }

            if(!is_array($references)) {
                $references = [$references];
            }
        }
        
        foreach($references as $idx => $reference) {
            $reference = trim($reference);
            if (empty($reference)) {
                unset($references[$idx]);
                continue;
            }

            $reference = strip_tags($reference);
            $reference = str_replace('&nbsp;', '', $reference);
            $reference = str_replace("\r\n", " ", $reference);
            $reference = str_replace("\n", " ", $reference);
            $reference = preg_replace('/^[0-9]+[\.\)].*?\W+/', '', $reference);
            $reference = preg_replace('/^\[[0-9]+\]\W+/', '', $reference);
            $reference = preg_replace('/\W+$/', '', $reference);

            if(strpos($reference, 'Fragrance of Mastership') !== false) {
                unset($references[$idx]);
                continue;
            }

            [$book, $otherParts] = explode(',', str_replace(' under ', ', under ', $reference));
            $book = trim($book);

            if(!isset($books[$book])) {
                $foundBook = findBook($book);
                if(!$foundBook) {
                    $books[$book] = $book;
                }
            }

            $foundBook = findBook($book);
            if($foundBook) {
                $reference = str_replace($book, $foundBook['title'], $reference);
            }

            $references[$idx] = [
                'reference' => $reference,
                'book' => $foundBook,
            ];
        }

        $references = array_values($references);
        //$references[] = "Fragrance of Mastership, tradition no. {$numericTag}";

        $notes = implode(" ", $notes);
        $notes = preg_replace("/\s+/", " ", $notes);
        $notesRegExp = '/<p>(.*?)<\/p>/s';
        $found = preg_match_all($notesRegExp, $notes, $matches);
        if ($found) {
            $notes = $matches[1];
        }

        if(!is_array($notes)) {
            $notes = [];
        }

        if(count($notes) > 0) {
            foreach($notes as $idx => $note) {
                $note = trim($note);
                if (empty($note)) {
                    unset($notes[$idx]);
                    continue;
                }

                $note = strip_tags($note);
                $note = str_replace('&nbsp;', '', $note);
                $note = str_replace("\r\n", " ", $note);
                $note = str_replace("\n", " ", $note);
                $note = preg_replace('/^[0-9]+[\.\)].*?\W+/', '', $note);
                $note = preg_replace('/^\[[0-9]+\]\W+/', '', $note);
                $note = preg_replace('/\W+$/', '', $note);

                $notes[$idx] = $note;
            }
        }



        $structuredArticle = [
            'title' => $title,
            'tradition_no' => $numericTag,
            'arabic' => $arabic,
            'english' => $english,
            'references' => $references,
            'notes' => $notes,
            'permalink' => $article['link']
        ];

        $structuredArticles[$numericTag] = $structuredArticle;
    }
}

uksort($books, function($a, $b) {
    return strcasecmp($a, $b);
});

foreach($books as $idx => $book) {
    $books[$idx] = [
        'title' => $book,
        'author' => '',
        'synonyms' => [
            $idx,
        ],
    ];
}

$books = array_values($books);

$booksFile = 'found-books.json';
file_put_contents($booksFile, json_encode($books, JSON_PRETTY_PRINT));


// Sort the articles by tradition number
ksort($structuredArticles);
$structuredArticles = array_values($structuredArticles);

$structuredArticlesFile = 'structured-articles.json';
file_put_contents($structuredArticlesFile, json_encode($structuredArticles, JSON_PRETTY_PRINT));

foreach($structuredArticles as $article) {
    echo "<h2>{$article['title']}</h2>";
    echo "<p>Tradition No: {$article['tradition_no']}</p>";
    echo "<p dir=rtl align=right>{$article['arabic']}</p>";
    echo nl2p($article['english']);
    if(!empty($article['references'])) {
        echo "<h3>References:</h3>";
        $references = $article['references'];
        echo '<ul>';
        foreach($references as $reference) {
            echo "<li>{$reference['reference']}</li>";
        }
        echo '</ul>';
    }
    if(!empty($article['notes'])) {
        echo "<h3>Notes:</h3>";
        echo toUnorderedList($article['notes']);
    }
    echo "<a href='{$article['permalink']}'>Read more</a>";
}

function get_numeric_tag($tagNamesById, $articleTags) {
    foreach ($articleTags as $tag) {
        if (is_numeric($tagNamesById[$tag])) {
            return $tagNamesById[$tag];
        }
    }
    return false;
}

function nl2p($text) {
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $text = str_replace("\n", "</p><p>", $text);
    return "<p>{$text}</p>";
}

function toUnorderedList($items) {
    $list = "<ul>";
    foreach ($items as $item) {
        $list .= "<li>{$item}</li>";
    }
    $list .= "</ul>";
    return $list;
}

function findBook($bookName) {
    global $booksMaster;
    foreach($booksMaster as $book) {
        $synonyms = $book['synonyms'];
        if(in_array($bookName, $synonyms)) {
            return $book;
        }
    }
    return false;
}