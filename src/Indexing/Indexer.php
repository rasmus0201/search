<?php

namespace Search\Indexing;

use Exception;
use PDO;
use Search\Indexing\Document;
use Search\Indexing\IndexTransformerInterface;
use Search\Indexing\Term;
use Search\NormalizerInterface;
use Search\TokenizerInterface;
use Search\Connectors\MysqlConnector;
use Search\Connectors\SQLiteConnector;
use Search\Repositories\DocumentIndexRepository;
use Search\Repositories\InfoRepository;
use Search\Repositories\TermIndexRepository;
use Search\Support\Config;

class Indexer
{
    private $config;
    private $transformer;
    private $normalizer;
    private $tokenizer;
    private $query;

    private $documentIndexRepository;
    private $infoRepository;
    private $termIndexRepository;

    private $chunkLimit = 1000;

    public function __construct(
        Config $config,
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
        $this->infoRepository = new InfoRepository($this->dbh);
        $this->termIndexRepository = new TermIndexRepository($this->dbh);
    }

    private function createDatabaseHandle(Config $config)
    {
        $connector = $this->createConnector($config);

        return $connector->connect($config);
    }

    private function createConnector(Config $config)
    {
        $map = [
            'mysql' => MySqlConnector::class,
            'sqlite' => SQLiteConnector::class,
        ];

        $driver = $config->getDriver();

        if (!isset($map[$driver])) {
            throw new Exception("Unsupported driver [{$driver}]");
        }

        return new $map[$driver]();
    }

    public function query($query, array $params = [])
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
        $this->termIndexRepository->createTableIfNotExists();
        $this->documentIndexRepository->createTableIfNotExists();

        $counter = 0;
        $this->dbh->beginTransaction();
        while ($row = $this->query->fetch(PDO::FETCH_ASSOC)) {
            $counter++;

            $document = $this->transformer->transform($row);

            $this->indexDocument($document);

            if ($counter % $this->chunkLimit == 0) {
                $this->success("Processed {$counter} rows");
            }

            if ($counter % 10000 == 0) {
                $this->dbh->commit();
                $this->infoRepository->updateByKey('total_documents', $counter);
                $this->dbh->beginTransaction();
                $this->success('Commited');
            }
        }

        $this->dbh->commit();
        $this->success('Commited');

        $this->infoRepository->updateByKey('total_documents', $counter);

        $this->success("Total rows {$counter}");
    }

    private function indexDocument($document)
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
                $this->error("Could not save term: '{$termItem->getTerm()}' with document id: {$termItem->getDocumentId()}");
            }
        }
    }

    private function saveTerm(Term $term)
    {
        try {
            $this->termIndexRepository->createOrUpdate($term);
        } catch (\Exception $e) {
            return false;
        }

        try {
            $term->setId($this->termIndexRepository->getTermId($term));
            $this->documentIndexRepository->createOrUpdate($term);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private function documentGuard($document)
    {
        if (!isset($document['id'])) {
            throw new Exception("Document must have an 'id' key.");
        }

        if (!isset($document['body'])) {
            throw new Exception("Document must have a 'body' key.");
        }
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
