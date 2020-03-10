<?php

namespace Search\Searching;

use Generator;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\InflectionRepository;
use Search\Repositories\TermIndexRepository;
use Search\Searching\SearchInterface;
use Search\Searching\SearchResult;
use Search\Support\DatabaseConfig;
use Search\Support\DB;
use Search\Support\Performance;
use Search\Support\SearchConfig;

class DefaultSearcher implements SearchInterface
{
    const USE_INFLECTIONS = true;
    const LOW_FREQ_CUTFOFF = 0.0025; // 0.25 %

    const CUTOFF_MULTIPLIER = 0.4;
    const MAX_DUPLICATE = 5;

    const EXACT_SCORE = 20;
    const INFLECTION_SCORE = 16;
    const PROXIMITY_SCORE = 1;
    const IS_LEMMA_MULTIPLIER = 1.3;
    const IS_REPEATED_MULTIPLIER = 0.5;

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

        $this->performance = new Performance;

        $dbh = DB::create($config)->getConnection();

        $this->documentIndexRepository = new DocumentIndexRepository($dbh);
        $this->inflectionRepository = new InflectionRepository($dbh);
        $this->infoRepository = new InfoRepository($dbh);
        $this->termIndexRepository = new TermIndexRepository($dbh);
    }

    public function search($searchPhrase, $numOfResults = 25) : SearchResult
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
            $this->settings->get('global.low_freq_cutoff', self::LOW_FREQ_CUTFOFF)
        );

        $inflections = array_column(
            $this->inflectionRepository->getByKeywords($searchWords),
            'inflection'
        );

        $queryWords = $this->filter(array_unique(array_merge($searchWords, $inflections)));

        $scores = $this->calcScores(
            $this->documentIndexRepository->getLazyByTerms($queryWords),
            $this->filter(array_column($terms, 'id')),
            $inflections,
            $totalDocuments
        );

        $best = $this->limitResults($scores, $this->settings->get('global.search_results', $numOfResults));

        $results = new SearchResult();
        $results->setIds(array_keys($best));
        $results->setScores($best);
        $results->setTotalHits(count($scores));
        $results->setStats($this->performance->get());

        return $results;
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

        $proximityScore = $this->settings->get('default.proximity_score', self::PROXIMITY_SCORE);
        $isLemmaMultiplier = $this->settings->get('default.is_lemma_multiplier', self::IS_LEMMA_MULTIPLIER);
        $isRepeatedMultiplier = $this->settings->get('default.is_repeated_multiplier', self::IS_REPEATED_MULTIPLIER);
        $inflectionScore = $this->settings->get('default.inflection_score', self::INFLECTION_SCORE);
        $exactScore = $this->settings->get('default.exact_score', self::EXACT_SCORE);

        foreach ($rows as $row) {
            if ($row['document_id'] != $currentArticle) {
                if ($currentArticle) {
                    $score -= $gapCount * $proximityScore;
                    $score -= $phraseLength - $matchCount;
                    $result[$currentArticle] = $isTermMatch ? ($score * $isLemmaMultiplier) : $score;
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
                $wordScore = $inflectionScore;
            } elseif (isset($termsByIds[$row['term_id']])) {
                $wordScore = $exactScore;
            }

            if (isset($usedWords[$row['term']])) {
                $score += $wordScore * $isRepeatedMultiplier;
            } else {
                $usedWords[$row['term']] = true;
                $score += $wordScore;
            }

            $matchCount += 1;
            $lastPosition = $row['position'];
        }

        // This is needed to catch the last phrase
        if ($currentArticle) {
            $score -= $gapCount * $proximityScore;
            $score -= $phraseLength - $matchCount;
            $result[$currentArticle] = $isTermMatch ? ($score * $isLemmaMultiplier) : $score;
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
        $minScore = $bestScore * $this->settings->get('default.result_cutoff_multiplier', self::CUTOFF_MULTIPLIER);
        $lastScore = $bestScore;
        $scoreLimit = $limit;
        $i = 0;

        $maxDuplicateScores = $this->settings->get('default.max_duplicate_scores', self::MAX_DUPLICATE);

        foreach ($result as $score) {
            if ($score < $minScore) {
                $scoreLimit = $i;
                break;
            }

            if ($score != $bestScore) {
                $same = ($score == $lastScore ? $same + 1 : 1);

                if ($same > $maxDuplicateScores) {
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