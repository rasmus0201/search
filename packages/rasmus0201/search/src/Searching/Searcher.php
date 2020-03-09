<?php

namespace Search\Searching;

use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\InflectionRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\DatabaseConfig;
use Search\Support\DB;
use Search\Support\Performance;

class Searcher
{
    const LIMIT_DOCUMENTS = 250000;
    const LOW_FREQ_CUTFOFF = 0.0025; // 0.25 %

    const BM25_BOOST = 0.75;
    const BM25_K1 = 1.2;

    private $config;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $inflectionRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $performance;

    public function __construct(
        DatabaseConfig $config,
        NormalizerInterface $normalizer,
        TokenizerInterface $tokenizer
    ) {
        $this->config = $config;
        $this->normalizer = $normalizer;
        $this->tokenizer = $tokenizer;

        $this->performance = new Performance();

        $dbh = DB::create($this->config)->getConnection();

        $this->documentIndexRepository = new DocumentIndexRepository($dbh);
        $this->inflectionRepository = new InflectionRepository($dbh);
        $this->infoRepository = new InfoRepository($dbh);
        $this->termIndexRepository = new TermIndexRepository($dbh);
    }

    /**
     * @param string $searchPhrase
     * @param int $numOfResults
     *
     * @return mixed[]
     */
    public function search($searchPhrase, $numOfResults = 25)
    {
        $this->performance->start();

        $searchWords = $this->tokenizer->tokenize(
            $this->normalizer->normalize($searchPhrase)
        );

        // Average amount of terms
        $averageDocumentLength = $this->infoRepository->getValueByKey('average_document_length');

        // Is the total number of documents (docCount)
        $totalDocuments = (int) $this->infoRepository->getValueByKey('total_documents');

        // Get only low frequency words
        list($keywords) = $this->termIndexRepository->getLowFrequencyTerms(
            $this->filter(array_unique($searchWords)),
            $totalDocuments,
            self::LOW_FREQ_CUTFOFF
        );

        // Get possible inflections
        $inflections = array_column(
            $this->inflectionRepository->getByKeywords($searchWords),
            'inflection'
        );

        $queryTerms = $this->termIndexRepository->getByKeywords(
            $this->filter(array_unique(array_merge($keywords, $inflections)))
        );

        $documents = $this->documentIndexRepository->getUniqueByTermIds(
            array_unique(array_column($queryTerms, 'id')),
            self::LIMIT_DOCUMENTS
        );

        $terms = $this->termIndexRepository->getByIds(
            array_unique(array_column($documents, 'term_id'))
        );

        $termsById = $this->termsById($terms);

        $documents = $this->constructDocuments($documents);

        // IDF for search terms
        $queryTermsIdf = $this->idf($queryTerms);

        // Scores
        $scores = [];

        $multiplierMap = [
            'inflection' => 0.8,
            'exact' => 1.1,
        ];

        foreach ($documents as $documentId => $documentTerms) {
            $documentLength = count($documentTerms);
            $termFrequencies = array_count_values(array_column($documentTerms, 'term_id'));

            $scores[$documentId] = 0;
            foreach ($queryTerms as $searchPosition => $queryTerm) {
                $termId = $queryTerm['id'];
                $idf = $queryTermsIdf[$termId];

                $tf = $this->bm25TF(
                    self::BM25_K1,
                    self::BM25_BOOST,
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
            //     $term = $terms[$termsById[$termId]];
            //
            //     // Proximity scoring
            //     $proximity = $this->proximityScore(
            //         $term['term'],
            //         $termPosition,
            //         $keywords
            //     );
            //     $proximityScore = $documentLength - min(abs($proximity), ($documentLength + 1));
            //
            //     $scores[$documentId] -= $proximityScore;
            // }
        }

        $best = $this->getBestMatches($scores, $numOfResults);

        return [
            'document_ids' => array_keys($best),
            'scores' => $best,
            'total_hits' => count($scores),
            'stats' => $this->performance->get(),
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
}
