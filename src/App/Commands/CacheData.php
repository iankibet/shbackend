<?php

namespace Iankibet\Shbackend\App\Commands;

use Iankibet\Shbackend\App\Repositories\CachingRepository;
use Illuminate\Console\Command;

class CacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:cache-data {action=cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $repo = new CachingRepository();

        if($this->argument('action') == 'clear') {
            $repo->emptyCacheKeys();
        } else {
            $cacheKeys = $repo->getCacheKeys();
            foreach ($cacheKeys as $key=>$value){
                $this->info("Dispaching $key");
                $res = dispatch(function() use ($key,$repo,$value){
                    $repo->cacheKeyPeriods($key, @$value['graphData']);
                })->onQueue('low');
                $this->info("Done");
            }
        }

        return Command::SUCCESS;
    }
}
