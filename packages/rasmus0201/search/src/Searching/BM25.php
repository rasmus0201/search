<?php

namespace Search\Searching;

use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\MySql\DocumentIndexRepository;
use Search\Repositories\MySql\InfoRepository;
use Search\Repositories\MySql\InflectionRepository;
use Search\Repositories\MySql\TermIndexRepository;
use Search\Repositories\SQLite\DocumentIndexRepository as SQLiteDocumentIndexRepository;
use Search\Repositories\SQLite\InflectionRepository as SQLiteInflectionRepository;
use Search\Repositories\SQLite\InfoRepository as SQLiteInfoRepository;
use Search\Repositories\SQLite\TermIndexRepository as SQLiteTermIndexRepository;
use Search\Searching\SearchInterface;
use Search\Support\DatabaseConfig;
use Search\Support\DB;
use Search\Support\Performance;
use Search\Support\SearchConfig;

class BM25 implements SearchInterface
{
    const LIMIT_DOCUMENTS = 50000;
    const LOW_FREQ_CUTFOFF = 0.0025; // 0.25 %

    const BM25_B = 0.75;
    const BM25_K1 = 1.2;

    private $settings;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $inflectionRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $performance;

    public function __construct(
        DatabaseConfig $config,
        SearchConfig $settings,
        NormalizerInterface $normalizer,
        TokenizerInterface $tokenizer
    ) {
        $this->settings = $settings;
        $this->normalizer = $normalizer;
        $this->tokenizer = $tokenizer;

        $this->performance = new Performance();

        $dbh = DB::create($config)->getConnection();

        if ($config->getDriver() === 'sqlite') {
            $this->documentIndexRepository = new SQLiteDocumentIndexRepository($dbh);
            $this->inflectionRepository = new SQLiteInflectionRepository($dbh);
            $this->infoRepository = new SQLiteInfoRepository($dbh);
            $this->termIndexRepository = new SQLiteTermIndexRepository($dbh);
        } else {
            $this->documentIndexRepository = new DocumentIndexRepository($dbh);
            $this->inflectionRepository = new InflectionRepository($dbh);
            $this->infoRepository = new InfoRepository($dbh);
            $this->termIndexRepository = new TermIndexRepository($dbh);
        }
    }

    public function search($searchPhrase, $numOfResults = 25) : SearchResult
    {
        $this->performance->start();

        $searchWords = $this->tokenizer->tokenize(
            $this->normalizer->normalize($searchPhrase)
        );

        // Average amount of terms
        $averageDocumentLength = $this->infoRepository->getValueByKey('average_document_length');

        // Is the total number of documents (docCount)
        $totalDocuments = (int) $this->infoRepository->getValueByKey('total_documents');

        // Get possible inflections
        $inflections = $this->inflectionRepository->getByKeywords($searchWords);
        $inflectionWords = array_column(
            $inflections,
            'inflection'
        );

        $queryTerms = $this->termIndexRepository->getByKeywords(
            $this->filter(array_unique(array_merge($searchWords, $inflectionWords)))
        );

        // Search only documents with low frequency words
        $lowFreqCutoff = $this->settings->get('global.low_freq_cutoff', self::LOW_FREQ_CUTFOFF);
        $queryTermsSearch = array_filter($queryTerms, function($term) use ($totalDocuments, $lowFreqCutoff) {
            return (($term['num_docs'] / $totalDocuments) < $lowFreqCutoff);
        });

        // If there is no lo-freq terms search for documents where all term ids exists
        if (empty($queryTermsSearch)) {
            $documents = $this->documentIndexRepository->getStrictUniqueByTermIds(
                array_unique(array_column($queryTerms, 'id')),
                $this->settings->get('bm25.max_query_documents', self::LIMIT_DOCUMENTS)
            );
        } else {
            $documents = $this->documentIndexRepository->getUniqueByTermIds(
                array_unique(array_column($queryTermsSearch, 'id')),
                $this->settings->get('bm25.max_query_documents', self::LIMIT_DOCUMENTS)
            );
        }

        $documents = $this->constructDocuments($documents);
        $inflections = $this->constructInflections($inflections);

        // IDF for search terms
        $queryTermsIdf = $this->idf($queryTerms);

        // Scores
        $scores = [];

        $k1 = $this->settings->get('bm25.k1', self::BM25_K1);
        $b = $this->settings->get('bm25.b', self::BM25_B);

        foreach ($documents as $documentId => $documentTerms) {
            $documentLength = count($documentTerms);
            $termFrequencies = array_count_values(array_column($documentTerms, 'term_id'));

            $scores[$documentId] = 0;
            foreach ($queryTerms as $searchPosition => $queryTerm) {
                $termId = $queryTerm['id'];
                $idf = $queryTermsIdf[$termId];

                $tf = $this->bm25TF(
                    $k1,
                    $b,
                    $termFrequencies[$termId] ?? 0,
                    $documentLength,
                    $averageDocumentLength
                );

                $scores[$documentId] += ($idf * $tf);
            }

            // TODO Needs another algorithm
            // foreach ($documentTerms as $documentTerm) {
            //     $termId = $documentTerm['term_id'];
            //     $termPosition = $documentTerm['position'];
            //     $term = $documentTerm['term'];
            //
            //     // Proximity scoring
            //     $bestProximity = $this->proximityScore(
            //         $term,
            //         $termPosition,
            //         $searchWords
            //     );
            //
            //     $proximityScore = $documentLength - min(abs($bestProximity), ($documentLength + 1));
            //
            //     $scores[$documentId] -= $proximityScore;
            // }
        }

        $best = $this->getBestMatches($scores, $this->settings->get('global.search_results', $numOfResults));

        $results = new SearchResult();
        $results->setIds(array_keys($best));
        $results->setScores($best);
        $results->setTotalHits(count($scores));
        $results->setStats($this->performance->get());

        return $results;
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

    private function constructInflections(array $inflections)
    {
        $result = [];
        foreach ($inflections as $inflection) {
            if (!isset($result[$inflection['term_id']])) {
                $result[$inflection['term_id']] = [];
            }

            $result[$inflection['term_id']][] = $inflection;
        }

        return $result;
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
}
