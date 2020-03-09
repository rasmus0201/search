<?php

namespace Search\Indexing;

use Exception;
use PDO;
use Search\Connectors\Traits\CanOpenConnections;
use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;
use Search\Indexing\Term;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InflectionRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\DatabaseConfig;

class Indexer
{
    use CanOpenConnections;

    private $config;
    private $transformer;
    private $normalizer;
    private $tokenizer;
    private $query;

    private $documentIndexRepository;
    private $inflectionRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $chunkLimit = 2500;
    private $commitLimit = 25000;

    public function __construct(
        DatabaseConfig $config,
        IndexTransformerInterface $transformer,
        NormalizerInterface $normalizer,
        TokenizerInterface $tokenizer
    ) {
        $this->config = $config;
        $this->transformer = $transformer;
        $this->normalizer = $normalizer;
        $this->tokenizer = $tokenizer;

        $this->dbh = $this->createDatabaseHandle($this->config);

        $this->documentIndexRepository = new DocumentIndexRepository($this->dbh);
        $this->inflectionRepository = new InflectionRepository($this->dbh);
        $this->infoRepository = new InfoRepository($this->dbh);
        $this->termIndexRepository = new TermIndexRepository($this->dbh);
    }

    public function setQuery($query, array $params = [])
    {
        $db = $this->createDatabaseHandle($this->config);
        $stmt = $db->prepare($query);

        foreach ($params as $param => $value) {
            $stmt->bindValue(':'.$param, $value, (ctype_digit($value) ? (PDO::PARAM_INT) : PDO::PARAM_STR));
        }

        $stmt->execute();

        // Can be something like "00000" on success so we need to type cast
        if ((int)$stmt->errorCode()) {
            throw new Exception($stmt->queryString.'; Error: '.print_r($stmt->errorInfo(), true));
        }

        $this->query = $stmt;
    }

    /**
     * Index entries (using TF-IDF and positional index)
     */
    public function run()
    {
        if (!$this->query) {
            throw new Exception("Query was not successful!");
        }

        $this->infoRepository->createTableIfNotExists();
        $this->inflectionRepository->createTableIfNotExists();
        $this->termIndexRepository->createTableIfNotExists();
        $this->documentIndexRepository->createTableIfNotExists();

        $counter = 0;
        $this->dbh->beginTransaction();
        while ($row = $this->query->fetch(PDO::FETCH_ASSOC)) {
            $counter++;

            $document = $this->transformer->transform($row);

            $this->indexDocument($document);

            if ($counter % $this->chunkLimit == 0) {
                $this->success("Processed {$counter} documents");
            }

            if ($counter % $this->commitLimit == 0) {
                $this->dbh->commit();
                $this->infoRepository->updateByKey('total_documents', $counter);
                $this->infoRepository->updateByKey('average_document_length', $this->documentIndexRepository->getAverageLength());
                $this->success('Commited');

                $this->dbh->beginTransaction();
            }
        }

        $this->dbh->commit();
        $this->success('Commited');

        $this->infoRepository->updateByKey('total_documents', $counter);
        $this->infoRepository->updateByKey('average_document_length', $this->documentIndexRepository->getAverageLength());

        $this->success("Total documents: {$counter}");
    }

    private function indexDocument(Document $document)
    {
        // Normalize input to only contain valid data
        $document->setDocument(
            $this->normalizer->normalize($document->getDocument())
        );

        $terms = $this->tokenizer->tokenize($document->getDocument());
        $freqList = array_count_values($terms);

        foreach ($terms as $position => $term) {
            if (empty($term)) {
                continue;
            }

            $termItem = new Term();
            $termItem->setDocumentId($document->getId());
            $termItem->setTerm($term);
            $termItem->setPosition($position);

            if ($freq = $freqList[$term]) {
                $termItem->setDocumentFrequency($freq);
            }

            if (!$this->saveTerm($termItem)) {
                $this->error("Could not save term: '{$termItem->getTerm()}' with document id: '{$termItem->getDocumentId()}'");
                continue;
            }

            $this->saveInflections($termItem, $document);
        }
    }

    private function saveTerm(Term $term)
    {
        try {
            $this->termIndexRepository->create($term);
        } catch (\Exception $e) {
            if ($e->getCode() != 23000) {
                return false;
            }

            // Update instead
            $this->termIndexRepository->incrementHits($term);
        }

        try {
            $term->setId($this->termIndexRepository->getTermId($term));
            $this->documentIndexRepository->create($term);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function saveInflections(Term $term, Document $document)
    {
        if ($term->getTerm() !== $document->getDocument()) {
            return true;
        }

        try {
            $this->inflectionRepository->createMany($term, $document->getInflections());
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function success($text)
    {
        $string = "\033[0;32m";
        $string .= $text . "\033[0m";

        echo $string.PHP_EOL;
    }

    private function error($text)
    {
        $string = "\033[0;31m";
        $string .= $text . "\033[0m";

        echo $string.PHP_EOL;
    }
}
