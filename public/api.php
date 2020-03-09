<?php

use App\Database\Database;
use Search\DefaultNormalizer;
use Search\DefaultTokenizer;
use Search\Searching\Searcher;
// use App\Dictionaries\Apollo\ApolloSearcher as Searcher;
use Search\Support\DatabaseConfig;

$res = [
    'document_ids' => [],
    'scores' => [],
    'dictionaries' => [],
    'stats' => [
        'raw' => [
            'execution_time' => 0,
            'memory_usage' => 0,
        ],
        'formatted' => [
            'execution_time' => '',
            'memory_usage' => '',
        ],
    ],
];

$request = [];
if ($_POST || $_GET || $_REQUEST) {
    $request = $_REQUEST;
}

if (!$request) {
    $request = json_decode(file_get_contents("php://input"), true);
}

if (!isset($request['q'])) {
    header('Content-Type: application/json');
    echo json_encode($res);
    die;
}

$config = new DatabaseConfig();
$config->setHost('localhost');
$config->setDatabase('search');
$config->setUsername('root');
$config->setPassword('');

$searcher = new Searcher(
    $config,
    new DefaultNormalizer(),
    new DefaultTokenizer()
);

$dicts = [];
$res = $searcher->search(trim($request['q']), 50);

if (count($res['document_ids'])) {
    $ids = implode(', ', $res['document_ids']);

    $stmt = Database::run("
        SELECT e.*, re.data as data, dict.id as dict_id, dict.name as dict_name FROM entries e

        INNER JOIN directions dir ON dir.id = e.direction_id
        INNER JOIN dictionaries dict ON dict.id = dir.dictionary_id
        INNER JOIN raw_entries re ON e.raw_entry_id = re.id

        WHERE e.`id` IN ($ids)
        ORDER BY FIELD(e.`id`, $ids)
    ");

    while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($entry['data']);
        $formatxml = new SimpleXMLElement($dom->saveXML());

        $xml = $formatxml->saveXML();

        $entry['data'] = $xml;
        $entry['score'] = round($res['scores'][$entry['id']], 2);

        if (!isset($dicts[$entry['dict_id']])) {
            $dicts[$entry['dict_id']] = [
                'dict_name' => $entry['dict_name'],
                'entries' => [$entry]
            ];

            continue;
        }

        $dicts[$entry['dict_id']]['entries'][] = $entry;
    }
}

$res['dictionaries'] = array_values($dicts);

header('Content-Type: application/json');
echo json_encode($res);
die;
