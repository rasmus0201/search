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

    const LIMIT_DOCUMENTS = 30000;
    const SEARCH_MAX_TOKENS = 12;

    private $config;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $memoryRealUsage = false;
    private $memory;
    private $timer;

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
     * @param string $searchPhrase
     * @param int $numOfResults
     *
     * @return mixed[]
     */
    public function search($searchPhrase, $numOfResults = 25)
    {
        $this->startStats();

        $keywords = $this->tokenizer->tokenize($searchPhrase);

        $scores = [];
        $totalDocuments = $this->infoRepository->getValueByKey('total_documents');

        $searchTerms = $this->termIndexRepository->getByKeywords(array_values(array_filter(array_unique($keywords))));
        $searchTerms = array_slice($searchTerms, 0, self::SEARCH_MAX_TOKENS);

        $searchTermIds = array_unique(array_column($searchTerms, 'id'));
        $searchTermsIdsById = array_flip($searchTermIds);

        // TODO Get the inflections from keywords
        //      with a stemmer (dictionary stemmer)
        $inflectionTerms = '';

        $documentIds = $this->documentIndexRepository->getUniqueIdsByTermIds($searchTermIds, self::LIMIT_DOCUMENTS);

        $documents = $this->documentIndexRepository->getByIds($documentIds);

        $terms = $this->termIndexRepository->getByIds(array_unique(array_column($documents, 'term_id')));
        $termsById = $this->termsById($terms);

        $documents = $this->constructDocuments($documents);

        $foundExact = false;

        foreach ($documents as $documentId => $document) {
            $documentLength = count($document);

            $termFrequencies = [];
            $headwords = [];
            foreach ($document as $documentTerm) {
                if (!isset($termFrequencies[$documentTerm['term_id']])) {
                    $termFrequencies[$documentTerm['term_id']] = 0;
                }

                $termFrequencies[$documentTerm['term_id']]++;

                $headwords[] = $terms[$termsById[$documentTerm['term_id']]]['term'];
            }

            $documentText = implode(' ', $headwords);

            // Check for exact match
            if (in_array($documentText, [$searchPhrase, implode(' ', $keywords)])) {
                $scores = [];
                $scores[$documentId] = [];
                $foundExact = true;

                break;
            }

            foreach ($document as $key => $documentTerm) {
                $termId = $documentTerm['term_id'];

                $termPosition = $documentTerm['position'];
                $term = $terms[$termsById[$termId]];

                // TODO Which one is most correct?
                // $tf = $termFrequencies[$termId] / $documentLength;
                // $tf = $term['num_hits']; // Stopwords is weighted really high here
                $tf = $termFrequencies[$termId];

                $df = $term['num_docs'];
                $idf = log($totalDocuments / max(1, $df));

                $score = $tf * $idf;

                // If a word matches the exact search but is not the same position
                $proximity = $this->proximityScore($term['term'], $termPosition, $keywords);
                $proximityScore = min(abs($proximity), 6);

                $score -= $proximityScore;

                if ($proximity === 0) {
                    $score *= 1.3;
                }

                if (!isset($searchTermsIdsById[$termId])) {
                    $score *= 0.8;
                }

                // TODO Maybe substract score if the document text's words have many that doesn't exist in the search phrase?

                $scores[$documentId] = isset($scores[$documentId]) ? ($scores[$documentId] + $score) : $score;
            }
        }

        $best = $this->getBestMatches($scores, $numOfResults);

        return [
            'document_ids' => array_keys($best),
            'total_hits' => count($scores),
            'stats' => $this->getStats(),
        ];
    }

    private function getBestMatches(array $scores, $numOfResults)
    {
        arsort($scores);

        return array_slice(
            $scores,
            0,
            $numOfResults,
            true
        );
    }

    private function proximityScore($search, $position, array $keywords)
    {
        $finds = [];
        foreach ($keywords as $key => $keyword) {
            if ($keyword === $search) {
                $finds[] = $key;
            }
        }

        if (empty($finds)) {
            return INF;
        }

        $positions = [];
        foreach ($finds as $foundPosition) {
            $positions[] = ($foundPosition - $position);
        }

        return min($positions);
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

    private function startStats()
    {
        $this->memory = memory_get_peak_usage($this->memoryRealUsage);
        $this->timer = microtime(true);
    }

    private function getStats()
    {
        $time = microtime(true) - $this->timer;
        $memory = memory_get_peak_usage($this->memoryRealUsage) - $this->memory;

        return [
            'raw' => [
                'execution_time' => $time,
                'memory_usage' => $memory,
            ],
            'formatted' => [
                'execution_time' => round($time, 7) * 1000 .' ms',
                'memory_usage' => round(($memory / 1024 / 1024), 2) . 'MiB',
            ],
        ];
    }
}
