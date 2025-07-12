<?php

namespace EgamiPeaks\Pizzazz\Commands;

use EgamiPeaks\Pizzazz\Services\PageCacheFlusher;
use Illuminate\Console\Command;

class PizzazzCommand extends Command
{
    public $signature = 'pizzazz:flush';

    public $description = 'Flush the Page cache';

    public function __construct(protected PageCacheFlusher $flusher)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->flusher->flush();

        return self::SUCCESS;
    }
}
