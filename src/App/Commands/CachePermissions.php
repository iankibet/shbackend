<?php

namespace Iankibet\Shbackend\App\Commands;

use App\Models\User;
use Iankibet\Shbackend\App\Repositories\PermissionsRepository;
use Illuminate\Console\Command;

class CachePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:cache-permissions';

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
        $roles = User::groupBy('role')->pluck('role')->toArray();
        $permissionsRepo = new PermissionsRepository();
        if(!count($roles)){
            //use default roles
            $roles = ['member','admin','client','customer'];
        }
       foreach ($roles as $role){
           if($role){
               $this->warn("working on $role permissions");
               $permissionsRepo->backupPermisions($role);
               $this->info("done");
           }
       }
        return 0;
    }
}
