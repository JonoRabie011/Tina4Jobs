<?php

namespace Tina4Jobs;

use Serializable;

interface Tina4Job
{

    public function __construct();

    public function handle(): void;

}