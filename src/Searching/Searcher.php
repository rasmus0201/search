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
}
