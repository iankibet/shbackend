<?php

namespace Shara\Framework\App\Commands;

use App\Models\User;
use App\Notifications\DatabaseBackupError;
use App\Notifications\DatabaseBackupSuccessful;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:backup-database {disk=backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            $disk = $this->argument('disk');
            $db = env('DB_DATABASE');
            $user = env('DB_USERNAME');
            $pass = env('DB_PASSWORD');
            $file_pre = $db.'_'.date('h_i_s_a');
            $file_name = $file_pre.'.sql';
            $endpoint = "tmp/".$file_name;
            $tar_name =$file_pre.'.tar.gz';
            $path = storage_path("app/tmp/".$tar_name);
            $sql_path = storage_path("app/tmp/".$file_name);
            $command = "mysqldump -u $user -p$pass $db > $sql_path --no-tablespaces";
            $this->comment("Dumping Database --- ");
            exec($command);
            $tar_command ="cd ".storage_path("app/tmp")." && tar -czvf ".$tar_name." $file_name";
            exec($tar_command);
            $file_size = $this->getFileSize($path);
            $this->info("Database dump complete");
            $this->comment("Uploading to $disk ...");
            $res =  Storage::disk($disk)->put('hauzisha_'.env('APP_ENV').'/'.date('Y/M/d').'/'.$tar_name,Storage::disk('local')->get("tmp/$tar_name"));            $this->info("Upload complete");
            $user = User::find(env('DB_BACKUP_EMAIL'));
            if($user){
                $this->comment("Notifying Admin");
                Notification::send($user,new DatabaseBackupSuccessful($tar_name,$file_size,$path));
            }
            exec("rm ".$path);
            exec("rm ".$sql_path);
            $this->info("Backup Complete");
        }catch (\Exception $e){
            $this->error('Failed with error '.$e->getMessage());
            $user = User::find(env('DB_BACKUP_EMAIL'));
            if($user){
                Notification::send($user,new DatabaseBackupError($e->getMessage()));
            }
        }

    }

    public function getFileSize($path)
    {
        $size = filesize($path);
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
