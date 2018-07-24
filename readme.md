# Usage
The package groups and counts similar words. The most used words in the text are used for building keywords `$keywords->string($wordsCount, $delimiter)`

```php
$keywords = new Keywords;

$keywords->config([
    'min_keyword_length' => 3, //ignore words with length less then 3
    'min_keyword_similarity' => 80, //group similar words with similarity at least 80%
    'encoding' => 'utf-8' //text encoding
]);

$keywords->content($text_or_html); //set text or html which would be scanned for keywords

$keywords->content(); //get content

$keywords->ignore($string, $type); //ignore $string of $type(word, symbol, regex) from content
$keywords->ignoreWord('are'); //ignore word
$keywords->ignoreSymbol('@'); //ignore symbol
$keywords->ignoreRegex('/[0-9]+/i'); //ignore regex

$keywords->replace('/halo/i', 'hello'); //replace

$keywords->generate(); //generate keywords

$keywords->get(); //get Collection of keywords (not sorted)
$keywords->string(10, ' '); //get 10 most used keywords as a string separated by space
```

Also it is possible to use functions in a chain
```php
$keywords = new Keywords;
$stringOfKeywords = $keywords->content($text_or_html)->generate()->string(20);

$collectionOfKeywords = (new Keywords)->content($text)->ignoreWord(['hello', 'world'])->generate()->get();
```
