<?php

namespace Search;

use Search\TokenizerInterface;

class DefaultTokenizer implements TokenizerInterface
{
    public function tokenize(string $string) : array
    {
        $tokens = preg_split('/[[:space:]]/', $string);

        return array_values(array_filter($tokens));

        // TODO Not to have?
        foreach ($tokens as $key => $token) {
            if (isset($this->stopwords[$token])) {
                $tokens[$key] = '';
            }
        }

        return array_values(array_filter($tokens));
    }

    private $stopwords = [
        'og' => true,
        'i' => true,
        'jeg' => true,
        'det' => true,
        'at' => true,
        'en' => true,
        'den' => true,
        'til' => true,
        'er' => true,
        'som' => true,
        'på' => true,
        'de' => true,
        'med' => true,
        'han' => true,
        'af' => true,
        'for' => true,
        'ikke' => true,
        'der' => true,
        'var' => true,
        'mig' => true,
        'sig' => true,
        'men' => true,
        'et' => true,
        'har' => true,
        'om' => true,
        'vi' => true,
        'min' => true,
        'havde' => true,
        'ham' => true,
        'hun' => true,
        'nu' => true,
        'over' => true,
        'da' => true,
        'fra' => true,
        'du' => true,
        'ud' => true,
        'sin' => true,
        'dem' => true,
        'os' => true,
        'op' => true,
        'man' => true,
        'hans' => true,
        'hvor' => true,
        'eller' => true,
        'hvad' => true,
        'skal' => true,
        'selv' => true,
        'her' => true,
        'alle' => true,
        'vil' => true,
        'blev' => true,
        'kunne' => true,
        'ind' => true,
        'når' => true,
        'være' => true,
        'dog' => true,
        'noget' => true,
        'ville' => true,
        'jo' => true,
        'deres' => true,
        'efter' => true,
        'ned' => true,
        'skulle' => true,
        'denne' => true,
        'end' => true,
        'dette' => true,
        'mit' => true,
        'også' => true,
        'under' => true,
        'have' => true,
        'dig' => true,
        'anden' => true,
        'hende' => true,
        'mine' => true,
        'alt' => true,
        'meget' => true,
        'sit' => true,
        'sine' => true,
        'vor' => true,
        'mod' => true,
        'disse' => true,
        'hvis' => true,
        'din' => true,
        'nogle' => true,
        'hos' => true,
        'blive' => true,
        'mange' => true,
        'ad' => true,
        'bliver' => true,
        'hendes' => true,
        'været' => true,
        'thi' => true,
        'jer' => true,
        'sådan' => true,
    ];
}
