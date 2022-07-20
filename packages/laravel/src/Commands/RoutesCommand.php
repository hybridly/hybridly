<?php

namespace Hybridly\Commands;

use Illuminate\Console\Command;
use Hybridly\RouteExtractor\RouteExtractor;

class RoutesCommand extends Command
{
    protected $signature = 'hybridly:routes';
    protected $description = 'Generates the routes definitions for the front-end.';
    protected $hidden = true;

    public function handle()
    {
        $this->output->write(resolve(RouteExtractor::class)->toJson());

        return self::SUCCESS;
    }
}
