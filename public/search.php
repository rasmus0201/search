<?php

use Search\DB;
use Search\DefaultNormalizer;
use Search\DefaultTokenizer;
use Search\Indexing\Indexer;
use Search\Searching\Searcher;
use Search\Support\Config;

$config = new Config();
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
    'execution_time' => 'No search time',
];
$dicts = [];

if (isset($_GET['q'])) {
    $res = $searcher->search(trim($_GET['q'], 10));
}

if (count($res['document_ids'])) {
    $ids = implode(', ', $res['document_ids']);

    $stmt = DB::run("
        SELECT e.*, dict.id as dict_id, dict.name as dict_name FROM entries e

        INNER JOIN directions dir ON dir.id = e.direction_id
        INNER JOIN dictionaries dict ON dict.id = dir.dictionary_id

        WHERE e.id IN ($ids)
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entry) {
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
    </head>
    <body>
        <form  method="get">
            <input type="search" style="width: 500px; padding: 5px;" name="q" value="<?php echo $_GET['q'] ?? ''; ?>">
            <input type="button" name="submit" value="Søg...">
        </form>
        <p>
            Search took: <?php echo $res['execution_time']; ?>
        </p>
        <?php foreach ($dicts as $dict): ?>
            <h2><?php echo $dict['dict_name']; ?></h2>

            <?php foreach ($dict['entries'] as $entry): ?>
                <h3 style="margin-bottom: 0;">
                    <?php echo $entry['headword']; ?>

                    <?php if ($entry['wordclass']): ?>
                        <small>(<?php echo $entry['wordclass']; ?>)</small>
                    <?php endif; ?>
                </h3>
                <small>EntryId: <?php echo $entry['id']; ?></small>
                <br>
                <span>Translation: </span>
                <span><?php echo $entry['translation']; ?></span>
                <br>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </body>
</html>
