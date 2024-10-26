<?php

namespace Tina4Jobs;

interface Tina4Job
{

    public function __construct();

    public function handle(): void;

}