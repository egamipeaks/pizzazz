<?php

namespace EgamiPeaks\Pizzazz\Commands;

use Illuminate\Console\Command;

class PizzazzCommand extends Command
{
    public $signature = 'pizzazz';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
