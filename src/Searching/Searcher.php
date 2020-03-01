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

    private $config;
    private $normalizer;
    private $tokenizer;

    private $documentIndexRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $maxDocuments = 500;

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

        $tfWeight  = 0; // 1.0
        $dlWeight  = 0; // 0.5
        $scores = [];
        $count     = $this->totalDocumentsInCollection();

        $terms = $this->termIndexRepository->getTermsByKeywords($keywords);

        foreach ($terms as $index => $term) {
            $df  = $term['num_docs'];
            $idf = log($count / max(1, $df));

            // dump([
            //     'term' => $term,
            //     'count' => $count,
            //     'df' => $df,
            //     'idf' => $idf,
            // ]);

            foreach ($this->getAllDocumentsForTermId($term['id']) as $document) {
                $docId = $document['document_id'];
                $tf    = $document['hit_count'];
                $num   = ($tfWeight + 1) * $tf;
                $denom = $tfWeight
                     * ((1 - $dlWeight) + $dlWeight)
                     + $tf;
                $score          = $idf * ($num / $denom);
                $scores[$docId] = isset($scores[$docId]) ? ($scores[$docId] + $score) : $score;
            }
        }

        arsort($scores);

        $totalHits = count($scores);
        $scores = array_slice(
            array_keys($scores),
            0,
            $numOfResults,
            true
        );

        $stopTimer = microtime(true);

        return [
            'document_ids'   => array_keys($scores),
            'total_hits'     => $totalHits,
            'execution_time' => round($stopTimer - $startTimer, 7) * 1000 .' ms'
        ];
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

    /**
     * @param int $termId
     *
     * @return mixed[]
     */
    private function getAllDocumentsForTermId($termId)
    {
        return $this->documentIndexRepository->getTermHitsInDocumentByTermId($termId, $this->maxDocuments);
    }

    /**
     * @return int
     */
    private function totalDocumentsInCollection()
    {
        return $this->infoRepository->getValueByKey('total_documents');
    }
}
