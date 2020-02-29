<?php

$res = ['execution_time' => ''];
$dicts = [];

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
