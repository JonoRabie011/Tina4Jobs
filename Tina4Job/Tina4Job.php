<?php

namespace Tina4Jobs;

abstract class Tina4Job implements Tina4JobInterface
{
    use Traits\Tina4Queueable;
}