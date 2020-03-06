<?php

namespace Search\Import\Apollo;

use Exception;
use PDO;
use Search\TransformerInterface;

class ApolloLemmatizer implements TransformerInterface
{
    private $dbh;

    public function setDatabaseHandle(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    public function transform(array $input) : array
    {

        if (!$this->dbh) {
            throw new Exception("Database handle was not set for transformer");
        }

        if (empty($input)) {
            return $input;
        }

        $placeholders = ':' . implode(', :', range(0, count($input) - 1));

        $params = [];
        foreach ($input as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->dbh->prepare("
            SELECT i.word, tmp.original

            FROM (
                SELECT i.word as original, i.lemma_id
                FROM inflections i
                WHERE i.word IN (".$placeholders.")
            ) tmp

            INNER JOIN inflections i ON tmp.lemma_id = i.lemma_id

            GROUP BY i.lemma_id
            ORDER BY CHAR_LENGTH(i.word) ASC
        ");

        $stmt->execute($params);

        if (!$inflections = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            return $input;
        }

        $bases = [];
        foreach ($inflections as $inflection) {
            if (isset($bases[$inflection['original']])) {
                continue;
            }

            $bases[$inflection['original']] = $inflection['word'];
        }

        $output = [];
        foreach ($input as $position => $term) {
            if (isset($bases[$term])) {
                $output[$position] = $bases[$term];
            } else {
                $output[$position] = $term;
            }
        }

        return $output;
    }
}
