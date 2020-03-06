<?php

namespace Search\Searching;

use Search\Connectors\Traits\CanOpenConnections;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\InflectionRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\Config;

class Searcher
{
    use CanOpenConnections;

    const LIMIT_DOCUMENTS = 250000;
    const SEARCH_MAX_TOKENS = 12;

    private $config;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $inflectionRepository;
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
        $this->inflectionRepository = new InflectionRepository($this->dbh);
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

        $keywords = $this->tokenizer->tokenize(
            $this->normalizer->normalize($searchPhrase)
        );

        $scores = [];

        $averageDocumentLength = $this->infoRepository->getValueByKey('average_document_length');

        $searchTerms = $this->termIndexRepository->getByKeywords($this->filter(array_unique($keywords)));
        $searchTerms = array_slice($searchTerms, 0, self::SEARCH_MAX_TOKENS);

        $searchTermIds = array_unique(array_column($searchTerms, 'id'));
        $searchTermsIdsById = array_flip($searchTermIds);

        $termInflections = $this->inflectionRepository->getByTermIds($this->filter(array_unique($searchTermIds)));
        $inflectionTermIds = array_unique(array_column($termInflections, 'term_id'));
        $inflectionTermIds = array_diff($inflectionTermIds, $searchTermIds);
        $inflectionTermsIdsById = array_flip($searchTermIds);
        $inflectionTerms = $this->termIndexRepository->getByIds($inflectionTermIds);

        // TODO - we still don't get the correct terms from inflections...

        $combinedTerms = array_merge($searchTerms, $inflectionTerms);
        $combinedTermIds = array_unique(array_merge($searchTermIds, $inflectionTermIds));

        $documentIds = $this->documentIndexRepository->getUniqueIdsByTermIds($combinedTermIds, self::LIMIT_DOCUMENTS);

        $documents = $this->documentIndexRepository->getByIds($documentIds);

        $terms = $this->termIndexRepository->getByIds(array_unique(array_column($documents, 'term_id')));
        $termsById = $this->termsById($terms);

        $documents = $this->constructDocuments($documents);

        // Should use document frequencies instead
        // $termFrequencies = [];
        // foreach ($searchTerms as $queryTerm) {
        //     if (!isset($termFrequencies[$queryTerm['id']])) {
        //         $termFrequencies[$queryTerm['id']] = 0;
        //     }
        //
        //     $termFrequencies[$queryTerm['id']]++;
        // }
        //
        // foreach ($inflectionTermIds as $inflectionTermId) {
        //     if (!isset($termFrequencies[$inflectionTermId])) {
        //         $termFrequencies[$inflectionTermId] = 0;
        //     }
        //
        //     $termFrequencies[$inflectionTermId]++;
        // }

        // IDF for search terms
        $searchTermsIdf = $this->idf($searchTerms);

        // b -> 0.75 (elasticsearch)
        $b = 0.75;

        // k1 -> 1.2 (elasticsearch)
        $k1 = 1.2;

        // Scores
        $bm25 = [];

        $multiplierMap = [
            'inflection' => 0.8,
            'exact' => 1.1,
        ];

        foreach ($documents as $documentId => $documentTerms) {
            $documentLength = count($documentTerms);

            $termsTokens = [];
            $termIds = [];
            foreach ($documentTerms as $documentTerm) {
                $termsTokens[] = $terms[$termsById[$documentTerm['term_id']]]['term'];
                $termIds[] = $documentTerm['term_id'];
            }
            $termFrequencies = array_count_values($termIds);

            $bm25[$documentId] = 0;
            foreach ($searchTerms as $searchPosition => $queryTerm) {
                $termId = $queryTerm['id'];
                $idf = $searchTermsIdf[$termId];

                $tf = $this->bm25TF(
                    $k1,
                    $b,
                    $termFrequencies[$termId] ?? 0,
                    $documentLength,
                    $averageDocumentLength
                );

                $type = isset($searchTermsIdsById[$termId]) ? 'exact' : 'inflection';

                $bm25[$documentId] += ($idf * $tf) * $multiplierMap[$type];
            }

            foreach ($documentTerms as $documentTerm) {
                $termId = $documentTerm['term_id'];
                $termPosition = $documentTerm['position'];
                $term = $terms[$termsById[$termId]];

                // TODO inflections
                $infls = array_flip(array_column($termInflections, 'inflection'));
                if (isset($infls[$term['term']])) {
                    $bm25[$documentId] += ($multiplierMap['inflection'] * 5);
                }

                // Proximity scoring
                // TODO Needs another algorithm
                $proximity = $this->proximityScore(
                    $term['term'],
                    $termPosition,
                    $keywords
                );
                $proximityScore = $documentLength - min(abs($proximity), ($documentLength + 1));

                $bm25[$documentId] -= $proximityScore;
            }
        }

        $best = $this->getBestMatches($bm25, $numOfResults);

        return [
            'document_ids' => array_keys($best),
            'scores' => $best,
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

    private function bm25TF($k1, $b, $termFrequency, $documentLength, $averageDocumentLength)
    {
        // f(q_i, D) -> How many times does the i'th query term occur in document D
        $freqInCurrentDoc = $termFrequency;

        // tf = (f(q_i, D) * (k1 + 1)) / (f(q_i, D) + k1 * (1 - b + b * (docNumTerms / avgDocNumTerms)))
        $tf = ($freqInCurrentDoc * ($k1 + 1)) / ($freqInCurrentDoc + $k1 * ((1 - $b) + $b * ($documentLength / $averageDocumentLength)));

        return $tf;
    }

    private function idf(array $terms)
    {
        // Is the total number of documents (docCount)
        $totalDocuments = (int) $this->infoRepository->getValueByKey('total_documents');

        $result = [];
        foreach ($terms as $term) {
            // f(q_i) -> is the number of documents which contain the i'th query term
            $freqInDocs = $term['num_docs'];

            // num = docCount - f(q_i) + 0.5
            $numerator = $totalDocuments - $freqInDocs + 0.5;

            // denom = f(q_i) + 0.5
            $denominator = $freqInDocs + 0.5;

            // idf = ln( 1 + (num / denom))
            $idf = log(1 + ($numerator / $denominator));

            $result[$term['id']] = $idf;
        }

        return $result;
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

    private function filter(array $keywords)
    {
        return array_values(array_filter($keywords));
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
