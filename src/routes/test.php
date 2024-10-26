<?php

use Tina4Jobs\TestJob;
use Tina4Jobs\Tina4Jobs;

\Tina4\Get::add("/test", function (\Tina4\Response $response) {

    $jobs = new Tina4Jobs();
    $jobs->addJob("test", new TestJob(["age" => random_int(1, 100)]));

    return $response ("Hello World");
});


\Tina4\Get::add("/test2", function (\Tina4\Response $response) {

    $jobs = new Tina4Jobs();
    $jobs->runJob("test");


    return $response ("Hello World");
});