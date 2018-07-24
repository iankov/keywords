<?php

namespace Iankov\Keywords;

use Illuminate\Support\Collection;

class Keywords
{
    protected $keywords;
    protected $config = [
        'min_keyword_length' => 3,
        'min_keyword_similarity' => 80, //in percent
        'encoding' => 'utf-8'
    ];
    protected $content = '';

    protected $ignore = [
        'words' => [
            "алтухов",
            "большой", "большая", "большие",
            "весь", "всей", "всея", "всего", "всех",
            "говорить", "говорил", "разговаривал", "разговаривать",
            "сказать", "сказал", "рассказал", "расскажет", "рассказать",
            "вы", "вам", "вас", "мне", "мы", "я", "ты", "они", "оно", "она", "он", "его", "наш", "него", "нее", "них",
            "так", "таки", "такой", "такая", "такое", "также",
            "свой", "своя", "своих", "себя", "сам", "твой", "твоя", "твоих", "их",
            "мочь", "мог", "может", "помочь", "поможет",
            "тот", "том", "этот", "это", "этом", "этому", "этих", "который", "которые", "которых", "оный",
            "знать", "знают", "знаешь", "знаю",
            "год", "месяц", "день", "неделя",
            "один", "одна", "одно", "одних",
            "у", "в", "и", "к", "от", "ото", "по", "с", "но", "о", "бы", "да", "до", "же", "из", "на", "та",
            "быть", "было", "будет", "был",
            "вот", "только", "как", "для", "что", "или", "еще", "когда", "где", "эта", "лишь", "уже",
            "нет", "если", "надо", "все", "чем", "при", "даже", "есть",
            "раз", "два", "разово", "дважды",
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
        ],
        'symbols' => [
            ",", ".", ";", ":", "\"", "#", "\$", "%", "^", "!", "@", "`", "~", "*", "-", "=", "+", "\\",
            "|", "/", ">", "<", "(", ")", "&", "?", "¹", "\t", "\r", "\n", "{", "}", "[", "]", "'", "“", "”", "•"
        ],
        'regex' => [
            "/&#?[a-z0-9]{2,8};/i", //html entities
            "/<script.*?<\/script>/is", //script tags
            "/<[\/\!]*?[^<>]*?>/si" //html tags
        ]
    ];

    protected $replace = [
        "/ё/" => "е"
    ];

    /**
     * Get or set config parameters
     *
     * @param array $cfg
     * @return array
     */
    public function config($cfg = [])
    {
        if(empty($cfg) || !is_array($cfg)){
            return $this->config;
        }

        return $this->config = array_merge($this->config, $cfg);
    }

    /**
     * @param null $text
     * @return mixed
     */
    public function content($text = null)
    {
        if(is_null($text)){
            return $this->content;
        }

        $this->content = $text;

        return null;
    }

    /**
     * Returns a collection of keywords
     *
     * @return Collection
     */
    public function get()
    {
        if(isset($this->keywords) && $this->keywords instanceof Collection){
            return $this->keywords;
        }
        return new Collection([]);
    }

    /**
     * Returns an imploded string of most common keywords
     *
     * @param mixed $limit
     * @param string $delimiter
     * @return string
     */
    public function string($delimiter = ', ', $limit = null)
    {
        $words = $this->get()->sortByDesc('count')->take($limit)->pluck('word')->all();
        return implode($delimiter, $words);
    }

    /**
     * Add a word to ignore
     *
     * @param mixed $word
     */
    public function ignoreWord($word)
    {
        $this->ignore($word, 'words');
    }

    /**
     * Add a symbol to ignore
     *
     * @param mixed $symbol
     */
    public function ignoreSymbol($symbol)
    {
        $this->ignore($symbol, 'symbols');
    }

    /**
     * Add a regular expression to ignore
     *
     * @param mixed $regex
     */
    public function ignoreRegex($regex)
    {
        $this->ignore($regex, 'regex');
    }

    /**
     * Add item to ignore
     *
     * @param array $items
     * @param string $type
     */
    public function ignore($items, $type = 'words')
    {
        $items = is_array($items) ? $items : [$items];
        $ignored = $this->ignore[$type];
        foreach ($items as $key => $item){
            if (in_array($item, $ignored)) {
                unset($items[$key]);
            }
        }

        $this->ignore[$type] = array_merge($ignored, $items);
    }

    /**
     * Add items to replace
     *
     * @param string $pattern
     * @param string $replacement
     */
    public function replace($pattern, $replacement)
    {
        if(is_array($pattern)){
            foreach($pattern as $k => $p){
                $r = $replacement;
                if(is_array($replacement)){
                    $r = array_get($replacement, $k, '');
                }

                $this->replace = array_merge($this->replace, [$p => $r]);
            }
        } else {
            $this->replace = array_merge($this->replace, [$pattern => $replacement]);
        }
    }

    /**
     * Generate keywords
     *
     * @return void
     */
    function generate()
    {
        $content = mb_strtolower($this->content, $this->config['encoding']);

        $content = preg_replace($this->ignore['regex'], " ", $content);

        foreach($this->replace as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        $words = array_map(function ($item) {
            return ' ' . $item . ' ';
        }, $this->ignore['words']);

        $content = str_replace($this->ignore['symbols'], ' ', $content);
        $content = str_replace($words, ' ', $content);
        $content = preg_replace("/\s+/", " ", $content);

        $occurance = [];
        $keywords = explode(" ", trim($content));
        foreach ($keywords as $keyword) {
            if (mb_strlen($keyword, $this->config['encoding']) < $this->config['min_keyword_length'])
                continue;

            foreach ($keywords as $key => $keyword2) {
                similar_text($keyword, $keyword2, $similarity);
                if ($similarity > $this->config['min_keyword_similarity']) {
                    unset($keywords[$key]);
                    if (isset($occurance[$keyword])) {
                        $occurance[$keyword]++;
                    } else {
                        $occurance[$keyword] = 1;
                    }
                }
            }

        }

        $keywords = [];
        foreach($occurance as $keyword => $count){
            $keywords[] = ['word' => $keyword, 'count' => $count];
        }
        $this->keywords = collect($keywords);
    }
}