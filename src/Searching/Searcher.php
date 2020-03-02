<?php

namespace Search\Searching;

use Search\Connectors\Traits\CanOpenConnections;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\Config;

class Searcher
{
    use CanOpenConnections;

    const LIMIT_DOCUMENTS = 1000;
    const SEARCH_MAX_TOKENS = 12;
    const TERM_FREQUENCY_WEIGHT = 1;
    const DOCUMENT_LENGTH_WEIGHT = 0.5;

    private $config;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $infoRepository;
    private $termIndexRepository;

    public function __construct(
        Config $config,
        NormalizerInterface $normalizer,
        TokenizerInterface $tokenizer
    ) {
        $this->config = $config;
        $this->normalizer = $normalizer;
        $this->tokenizer = $tokenizer;

        $this->dbh = $this->createDatabaseHandle($this->config);

        $this->documentIndexRepository = new DocumentIndexRepository($this->dbh);
        $this->infoRepository = new InfoRepository($this->dbh);
        $this->termIndexRepository = new TermIndexRepository($this->dbh);
    }

    /**
     * @param string $phrase
     * @param int $numOfResults
     *
     * @return mixed[]
     */
    public function search($phrase, $numOfResults = 25)
    {
        $startTimer = microtime(true);
        $keywords   = $this->tokenizer->tokenize($phrase);

        $scores = [];
        $totalDocuments = $this->infoRepository->getValueByKey('total_documents');

        // TODO What if some keywords was not found as a term?
        $searchTerms = $this->termIndexRepository->getByKeywords($keywords);
        $searchTerms = array_slice($searchTerms, 0, self::SEARCH_MAX_TOKENS);

        $searchTermIds = array_unique(array_column($searchTerms, 'id'));
        $documentIds = $this->documentIndexRepository->getUniqueIdsByTermIds($searchTermIds, self::LIMIT_DOCUMENTS);
        $documents = $this->documentIndexRepository->getByIds($documentIds);

        $terms = $this->termIndexRepository->getByIds(array_unique(array_column($documents, 'term_id')));
        $termsById = $this->termsById($terms);

        $documents = $this->constructDocuments($documents);


        foreach ($documents as $documentId => $document) {
            $documentLength = count($document);

            $termFrequencies = [];
            foreach ($document as $documentTerm) {
                if (!isset($termFrequencies[$documentTerm['term_id']])) {
                    $termFrequencies[$documentTerm['term_id']] = 0;
                }

                $termFrequencies[$documentTerm['term_id']]++;
            }

            foreach ($document as $key => $documentTerm) {
                $termId = $documentTerm['term_id'];
                $term = $terms[$termsById[$termId]];

                $tf = $termFrequencies[$termId] / $documentLength;

                $df = $term['num_docs'];
                $idf = log($totalDocuments / max(0.1, $df));

                $score = $tf * $idf;

                if (isset($this->stopwords[$term['term']])) {
                    $score *= 0.3;
                }

                if (in_array($termId, $searchTermIds)) {
                    $score *= 1.3;
                }

                $scores[$documentId] = isset($scores[$documentId]) ? ($scores[$documentId] + $score) : $score;
            }
        }

        arsort($scores);

        $totalHits = count($scores);
        $scores = array_slice(
            $scores,
            0,
            $numOfResults,
            true
        );

        // foreach ($scores as $documentId => $score) {
        //     echo '<pre>';
        //     var_dump($documentId, $score);
        //     var_dump($documents[$documentId]);
        //     echo '<br>';
        //     echo '<br>';
        //     echo '<pre>';
        // }
        // die;

        $stopTimer = microtime(true);

        return [
            'document_ids'   => array_keys($scores),
            'total_hits'     => $totalHits,
            'execution_time' => round($stopTimer - $startTimer, 7) * 1000 .' ms'
        ];
    }

    private function termsById(array $terms)
    {
        return array_combine(array_column($terms, 'id'), array_keys($terms));
    }

    private function constructDocuments(array $documents)
    {
        $result = [];
        foreach ($documents as $document) {
            if (!isset($result[$document['document_id']])) {
                $result[$document['document_id']] = [];
            }

            $result[$document['document_id']][] = $document;
        }

        return $result;
    }

    /**
     * @param int $termId
     *
     * @return int
     */
    private function totalMatchingDocuments($termId)
    {
        return $this->documentIndexRepository->getDocumentHitsByTermId($termId);
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
        'a' => true,
        'an' => true,
        'and' => true,
        'are' => true,
        'as' => true,
        'at' => true,
        'be' => true,
        'but' => true,
        'by' => true,
        'for' => true,
        'if' => true,
        'in' => true,
        'into' => true,
        'is' => true,
        'it' => true,
        'no' => true,
        'not' => true,
        'of' => true,
        'on' => true,
        'or' => true,
        'such' => true,
        'that' => true,
        'the' => true,
        'their' => true,
        'then' => true,
        'there' => true,
        'these' => true,
        'they' => true,
        'this' => true,
        'to' => true,
        'was' => true,
        'will' => true,
        'with' => true,
    ];
}
