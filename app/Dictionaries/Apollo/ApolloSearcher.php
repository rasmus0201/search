<?php

namespace App\Dictionaries\Apollo;

use Generator;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\InflectionRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\DatabaseConfig;
use Search\Support\DB;
use Search\Support\Performance;

class ApolloSearcher
{
    const USE_INFLECTIONS = true;
    const LOW_FREQ_CUTFOFF = 0.0025; // 0.25 %

    const CUTOFF_MULTIPLIER = 0.4;
    const MAX_DUPLICATE = 5;

    const EXACT_SCORE = 20;
    const INFLECTION_SCORE = 16;
    const NOT_QUERY_TERM_SCORE = 4;
    const PROXIMITY_SCORE = 1;
    const IS_LEMMA_MULTIPLIER = 1.3;
    const IS_REPEATED_MULTIPLIER = 0.5;

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

        $this->performance = new Performance;

        $dbh = DB::create($this->config)->getConnection();

        $this->documentIndexRepository = new DocumentIndexRepository($dbh);
        $this->inflectionRepository = new InflectionRepository($dbh);
        $this->infoRepository = new InfoRepository($dbh);
        $this->termIndexRepository = new TermIndexRepository($dbh);
    }

    public function search($searchPhrase, $numOfResults = 25)
    {
        $this->performance->start();

        $searchWords = $this->tokenizer->tokenize(
            $this->normalizer->normalize($searchPhrase)
        );

        // Is the total number of documents (docCount)
        $totalDocuments = (int) $this->infoRepository->getValueByKey('total_documents');

        // Get only low frequency words
        list($searchWords, $terms) = $this->termIndexRepository->getLowFrequencyTerms(
            $this->filter(array_unique($searchWords)),
            $totalDocuments,
            self::LOW_FREQ_CUTFOFF
        );

        $inflections = array_column(
            $this->inflectionRepository->getByKeywords($searchWords),
            'inflection'
        );

        $queryWords = $this->filter(array_unique(array_merge($searchWords, $inflections)));

        $result = $this->calcScores(
            $this->documentIndexRepository->getLazyByTerms($queryWords),
            $this->filter(array_column($terms, 'id')),
            $inflections,
            $totalDocuments
        );

        $result = $this->limitResults($result, $numOfResults);

        return [
            'document_ids' => array_keys($result),
            'scores' => $result,
            'total_hits' => count($result),
            'stats' => $this->performance->get(),
        ];
    }

    private function calcScores(Generator $rows, array $termIds, array $inflections, $totalDocuments)
    {
        $inflectionsById = array_flip($inflections);
        $termsByIds = array_flip($termIds);

        $result = [];
        $currentArticle = null;
        // All these variables will be set the first time through the foreach
        $isTermMatch = false;
        $phraseLength = 0;
        $matchCount = 0;
        $gapCount = 0;
        $usedWords = [];
        $score = 0;
        $lastPosition = 0;

        foreach ($rows as $row) {
            if ($row['document_id'] != $currentArticle) {
                if ($currentArticle) {
                    $score -= $gapCount * self::PROXIMITY_SCORE;
                    $score -= $phraseLength - $matchCount;
                    $result[$currentArticle] = $isTermMatch ? ($score * self::IS_LEMMA_MULTIPLIER) : $score;
                }

                $currentArticle = $row['document_id'];
                $isTermMatch = isset($termsByIds[$row['term_id']]);
                $phraseLength = $row['length'];
                $matchCount = 0;
                $gapCount = 0;
                $usedWords = [];
                $score = 0;
            } else {
                $gapCount += $row['position'] - $lastPosition - 1;
            }

            $wordScore = 0;

            if (isset($inflectionsById[$row['term_id']])) {
                $wordScore = self::INFLECTION_SCORE;
            } elseif (isset($termsByIds[$row['term_id']])) {
                $wordScore = self::EXACT_SCORE;
            }

            if (isset($usedWords[$row['term']])) {
                $score += $wordScore * self::IS_REPEATED_MULTIPLIER;
            } else {
                $usedWords[$row['term']] = true;
                $score += $wordScore;
            }

            $matchCount += 1;
            $lastPosition = $row['position'];
        }

        // This is needed to catch the last phrase
        if ($currentArticle) {
            $score -= $gapCount * self::PROXIMITY_SCORE;
            $score -= $phraseLength - $matchCount;
            $result[$currentArticle] = $isTermMatch ? ($score * self::IS_LEMMA_MULTIPLIER) : $score;
        }

        arsort($result);

        return $result;
    }

    /**
     * Limit the number of results returned.
     *
     * @param int[] $result
     * @param int $limit
     *
     * @return int[]
     */
    private function limitResults(array $result, $limit)
    {
        $bestScore = reset($result);
        $minScore = $bestScore * self::CUTOFF_MULTIPLIER;
        $lastScore = $bestScore;
        $scoreLimit = $limit;
        $i = 0;

        foreach ($result as $score) {
            if ($score < $minScore) {
                $scoreLimit = $i;
                break;
            }

            if ($score != $bestScore) {
                $same = ($score == $lastScore ? $same + 1 : 1);

                if ($same > self::MAX_DUPLICATE) {
                    $scoreLimit = $i;
                    break;
                }
            }

            $lastScore = $score;
            $i += 1;

            if ($i == $limit) {
                break;
            }
        }

        return array_slice($result, 0, min($scoreLimit, $limit), true);
    }

    private function filter(array $keywords)
    {
        return array_values(array_filter($keywords));
    }
}
