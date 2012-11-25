<?php

require_once('src/Query.php');
require_once('src/QueryIterator.php');

class Database {
    private $records;
}

$repository = new Query(\json_decode(\file_get_contents('config.json')));

echo '<pre>';

$types = $repository->map(function ($emit, $value) {
    $emit($value->{'@type'}, $value);
});

$types->Lecture[0]->students[] = $repository['bde103465efb092a169e3e61c473f0ef']->reference();

foreach ($types->Lecture[0]->students as $student) {
    print_r($student->name."\n");
}

print_r($types->Lecture[0]->professor->name);

echo '</pre>';

