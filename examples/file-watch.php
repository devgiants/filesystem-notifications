<?php

use React\EventLoop\Factory;
use Calcinai\Rubberneck\Observer;

include __DIR__.'/../vendor/autoload.php';

$loop = Factory::create();
$observer = new Observer($loop);

$observer->onModify(function($file_name){
    echo "Modified: $file_name\n";
});

$observer->onCreate(function($file_name){
    echo "Created: $file_name\n";
});

$observer->onDelete(function($file_name){
    echo "Deleted: $file_name\n";
});


$observer->watch('/tmp/*.txt');

$loop->run();