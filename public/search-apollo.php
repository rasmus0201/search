<?php

use Search\Support\StaticDB;
use Search\DefaultNormalizer;
use Search\DefaultTokenizer;
use Apollo\ApolloSearcher as Searcher;
use Search\Support\DatabaseConfig;

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

$res = [
    'document_ids' => [],
    'scores' => [],
    'stats' => [
        'raw' => [
            'execution_time' => 0,
            'memory_usage' => 0,
        ],
        'formatted' => [
            'execution_time' => 'No search time',
            'memory_usage' => '',
        ],
    ]
];
$dicts = [];

if (isset($_GET['q'])) {
    $res = $searcher->search(trim($_GET['q']), 50);
}

if (count($res['document_ids'])) {
    $ids = implode(', ', $res['document_ids']);

    $stmt = StaticDB::run("
        SELECT e.*, re.data as data, dict.id as dict_id, dict.name as dict_name FROM entries e

        INNER JOIN directions dir ON dir.id = e.direction_id
        INNER JOIN dictionaries dict ON dict.id = dir.dictionary_id
        INNER JOIN raw_entries re ON e.raw_entry_id = re.id

        WHERE e.`id` IN ($ids)
        ORDER BY FIELD(e.`id`, $ids)
    ");

    while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <title>Search</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/styles/vs.min.css">
        <style media="screen">
            .result h3 {
                margin-bottom: 0;
            }

            .result {
                margin-bottom: 10px;
                padding: 8px;
                background: #fafafa;
            }

            .result .xml {
                background: #fafafa;
            }
        </style>
    </head>
    <body>
        <form  method="get">
            <input type="search" style="width: 500px; padding: 5px;" name="q" value="<?php echo $_GET['q'] ?? ''; ?>">
            <input type="button" name="submit" value="Søg...">
        </form>
        <p>
            <span>Search took: <?php echo $res['stats']['formatted']['execution_time']; ?></span>
            <br>
            <span>Results: <?php echo count($res['document_ids']); ?></span>
            <br>
            <span>Memory usage: <?php echo $res['stats']['formatted']['memory_usage']; ?></span>
        </p>
        <?php if (empty($dicts)): ?>
            <p>No results.</p>
        <?php endif; ?>
        <?php foreach ($dicts as $dict): ?>
            <h2><?php echo $dict['dict_name']; ?></h2>

            <?php foreach ($dict['entries'] as $entry): ?>
                <div class="result">
                    <h3><?php echo $entry['headword']; ?></h3>
                    <small>EntryId: <?php echo $entry['id']; ?></small>
                    <small>Score: <?php echo round($res['scores'][$entry['id']], 3); ?></small>
                    <?php
                        $dom = new DOMDocument('1.0', 'utf-8');
                        $dom->preserveWhiteSpace = false;
                        $dom->formatOutput = true;
                        $dom->loadXML($entry['data']);
                        $formatxml = new SimpleXMLElement($dom->saveXML());

                        $xml = $formatxml->saveXML();
                    ?>
                    <pre class="xml"><?php echo $xml; ?></pre>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore-min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/highlight.min.js"></script>
        <script>
            (function() {
                var highlights = document.querySelectorAll('.xml');

                for (const hightlight of highlights) {
                    esc = _.escape(hightlight.innerHTML);
                    hightlight.innerHTML = esc;
                    hljs.highlightBlock(hightlight);
                }
            })();
        </script>
    </body>
</html>
