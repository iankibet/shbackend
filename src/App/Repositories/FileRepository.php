<?php
/**
 * Created by PhpStorm.
 * User: iankibet
 * Date: 7/25/18
 * Time: 8:13 AM
 */

namespace Iankibet\Shbackend\App\Repositories;
use App\Repositories\LogsRepository;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function env;
use function getUser;
use function now;
use function request;
use function storage_path;

class FileRepository
{
    public static function moveMany($files,$public=false){
        try{
            $uploaded_files = [];
            foreach ($files as $file) {
                $originan_name = $file->getClientOriginalName();
                $file_type = $file->getClientMimeType();
                $file_size = $file->getSize();
                $arr = explode('.', $originan_name);
                $ext = $arr[count($arr) - 1];
                $file_name = Str::slug(str_replace($ext, '', $originan_name)) . '.' . $ext;
                if ($public) {
                    $pre = 'public';
                } else {
                    $pre = '';
                }
                $path = '/files/' . Carbon::now()->format('Y/m/d');
                $new_path = $pre . '/' . $path;
                $public_path = '/storage' . $path;
                if ($pre == 'public') {
                    $path = '/public' . $path;
                }
                $new_name = getUser()->id.'-'.Str::random(3) . '_' . date('H_i_s') . '_' . $file_name;
                $disk = env('FILESYSTEM_DRIVER', 'local');
                if ($public) {
                    $disk = 'local';
                }
                Storage::disk($disk)->putFileAs($new_path, $file, $new_name);
                if ($public) {
                    $pre = '/storage';
                }
                $uploaded_files[] = [
                    'file_name' => $originan_name,
                    'file_size' => $file_size,
                    'path' => $path . '/' . $new_name,
                    'public_path' => $public_path . '/' . $new_name,
                    'file_type' => $file_type,
                    'uploaded' => true,
                    'ext' => $ext,
                    'disk' => $disk
                ];
            }
            return ['uploaded'=>true,'files'=>$uploaded_files];
        }catch(\Exception $e){
            return [
                'uploaded'=>false,
                'error'=>$e->getMessage()
            ];
        }
    }

    public static function move($file,$public=false,$path = null){
        if($public){
            $pre = 'public';
        }else{
            $pre = '';
        }
        if(!$path)
            $path = '/files/'.Carbon::now()->format('Y/m/d');
        $new_path = $pre.'/'.$path;
        $public_path = '/storage'.$path;
        if($pre == 'public'){
            $path = '/public'.$path;
        }
        if(is_string($file)){
            $file = str_replace(storage_path("app"),'',$file);
//            dd($file);
            $size = Storage::size($file);
            $file_name = request('file_name');
            $file_path = $file;
            $arr = explode('/',$file_path);
            $new_name = $arr[count($arr)-1];
            $arr2 = explode('.',$file_name);
            $ext = $arr2[count($arr2)-1];
            Storage::put($new_path.'/'.$new_name,Storage::get($file_path));
            Storage::delete($file_path);
            return [
                'file_name'=>$file_name,
                'name'=>$file_name,
                'file_size'=>$size,
                'size'=>$size,
                'path'=>$path.'/'.$new_name,
                'public_path'=>$public_path.'/'.$new_name,
                'file_type'=>$ext,
                'uploaded'=>true,
                'ext'=>$ext,
            ];
        }
        try{
            $originan_name = $file->getClientOriginalName();
            $file_type = $file->getClientMimeType();
            $file_size = $file->getSize();
            $arr = explode('.',$originan_name);
            $ext = $arr[count($arr)-1];
            $file_name = Str::slug(str_replace($ext,'',$originan_name)).'.'.$ext;

            $new_name = getUser()->id.'-'.Str::random(3).'_'.date('H_i_s').'_'.$file_name;
            $disk = env('FILESYSTEM_DRIVER', 'local');
            if($public){
                $disk = 'local';
            }
            Storage::disk($disk)->putFileAs($new_path,$file,$new_name);
            if($public){
                $pre = '/storage';
            }
            return (object) [
                'file_name'=>$originan_name,
                'name'=>$originan_name,
                'file_size'=>$file_size,
                'size'=>$file_size,
                'path'=>$path.'/'.$new_name,
                'public_path'=>$public_path.'/'.$new_name,
                'file_type'=>$file_type,
                'uploaded'=>true,
                'ext'=>$ext,
                'disk'=>$disk
            ];
        }catch(\Exception $e){
            return [
                'uploaded'=>false,
                'error'=>$e->getMessage()
            ];
        }
    }
    public static function moveProfilePicture($file,$public=false,$path = null){
        if($public){
            $pre = 'public';
        }else{
            $pre = '';
        }
        if(!$path)
            $path = '/profile-pics/'.Carbon::now()->format('Y/m/d');
        $new_path = $pre.'/'.$path;
        $public_path = '/storage'.$path;
        if($pre == 'public'){
            $path = '/public'.$path;
        }
        if(is_string($file)){
            $file = str_replace(storage_path("app"),'',$file);
//            dd($file);
            $size = Storage::size($file);
            $file_name = request('file_name');
            $file_path = $file;
            $arr = explode('/',$file_path);
            $new_name = $arr[count($arr)-1];
            $arr2 = explode('.',$file_name);
            $ext = $arr2[count($arr2)-1];
            Storage::put($new_path.'/'.$new_name,Storage::get($file_path));
            Storage::delete($file_path);
            return [
                'file_name'=>$file_name,
                'name'=>$file_name,
                'file_size'=>$size,
                'size'=>$size,
                'path'=>$path.'/'.$new_name,
                'public_path'=>$public_path.'/'.$new_name,
                'file_type'=>$ext,
                'uploaded'=>true,
                'ext'=>$ext,
            ];
        }
        try{
            $originan_name = $file->getClientOriginalName();
            $file_type = $file->getClientMimeType();
            $file_size = $file->getSize();
            $arr = explode('.',$originan_name);
            $ext = $arr[count($arr)-1];
            $file_name = Str::slug(str_replace($ext,'',$originan_name)).'.'.$ext;

            $new_name = getUser()->id.'-'.Str::random(3).'_'.date('H_i_s').'_'.$file_name;
            $disk = env('FILESYSTEM_DRIVER', 'local');
            if($public){
                $disk = 'local';
            }
            Storage::disk($disk)->putFileAs($new_path,$file,$new_name);
            if($public){
                $pre = '/storage';
            }
            return (object) [
                'file_name'=>$originan_name,
                'name'=>$originan_name,
                'file_size'=>$file_size,
                'size'=>$file_size,
                'path'=>$path.'/'.$new_name,
                'public_path'=>$public_path.'/'.$new_name,
                'file_type'=>$file_type,
                'uploaded'=>true,
                'ext'=>$ext,
                'disk'=>$disk
            ];
        }catch(\Exception $e){
            return [
                'uploaded'=>false,
                'error'=>$e->getMessage()
            ];
        }
    }

    public static function download($file){
        $order = Order::find($file->order_id);
        LogsRepository::storeLog('download_file','Downloaded file:<b> # '.$file->id.'</b>, <b>'.$file->name.'</b> for order: ID #<b>'.$order->id.'</b>',$order->id);

        if(!$order){
            return \response()->download(storage_path('app/'.$file->path),$file->name);
        }
        if($file->disk != 'local'){
            return Storage::disk($file->disk)->download($file->path,$order->order_number.'_'.$file->name);
        }
        if (file_exists(storage_path('app/'.$file->path)))
            return \response()->download(storage_path('app/'.$file->path),$order->order_number.'_'.$file->name);
        if (file_exists(storage_path('app/'.str_replace('public/','',$file->path))))
            return \response()->download(storage_path('app/'.str_replace('public/','',$file->path)),$order->order_number.'_'.$file->name);
        if(file_exists($file->path))
            return \response()->download($file->path,$order->order_number.'_'.$file->name);
        $slug = Str::slug($file->name);
        $file_path = storage_path('app/'.$file->path.$slug);
        $file_path = str_replace('-'.$file->type,'.'.$file->type,$file_path);
        if(file_exists($file_path))
            return \response()->download($file_path,$order->order_number.'_'.$file->name);
        $slug = str_replace($file->type,'.'.$file->type,$slug);
        $file_path = storage_path('app/'.$file->path.$slug);
        if(file_exists($file_path))
            return \response()->download($file_path);
        $file_path = storage_path('app/'.$file->path.$file->name);
        if(file_exists($file_path))
            return \response()->download($file_path);
        $file_path = storage_path('app/'.$file->path.Str::slug($file->name));
        return \response()->download($file_path);
    }

    public static function optimizeCloudinary($file){
        if($file->cloudinary_id)
            return;
        $client = new Client();
        $path = storage_path('app'.$file->path);
        $url = "https://api.cloudinary.com/v1_1/".env('CLOUD_NAME')."/image/upload";
        if(file_exists($path)){
            $result = $client->request('POST', $url,
                [
                    'multipart' => [
                        [
                            'name'=>'file',
                            'contents'=>file_get_contents($path),
                            'filename'=>$file->name
                        ],
                        [
                            'name'=>'api_key',
                            'contents'=>env('CLOUD_API_KEY')
                        ],
                        [
                            'name'=>'upload_preset',
                            'contents'=>'ml_default'
                        ]
                    ]
                ]
            );
            $res = json_decode((string)$result->getBody());
            $cloudinary_id = $res->asset_id;
            $cloudinary_url = $res->url;
            $arr = explode('/',$cloudinary_url);
            $version = $arr[count($arr)-2];
            $cloudinary_url = str_replace($version,'q_auto:good',$cloudinary_url);
            $file->cloudinary_id = $cloudinary_id;
            $file->url = $cloudinary_url;
            $file->update();
            self::moveToAws($file);
            return $file;
        } else {
            $file->cloudinary_id = '-1';
            $file->update();
            return false;
        }
    }

    public static function optimizeWithImageKit($file){
            if($file->imagekit_id){
                self::moveToAws($file);
                return;
            }
            $client = new Client();
            $path = storage_path('app'.$file->path);
            $url = "https://".urlencode(env('IMAGEKIT_KEY')).":@upload.imagekit.io/api/v1/files/upload";
            if(file_exists($path)){
                $result = $client->request('POST', $url,
                    [
                        'multipart' => [
                            [
                                'name'=>'file',
                                'contents'=>base64_encode(file_get_contents($path)),
                                'fileName'=>$file->name
                            ],
                            [
                                'name'=>'fileName',
                                'contents'=>$file->name
                            ],
                            [
                                'name'=>'upload_preset',
                                'contents'=>'ml_default'
                            ]
                        ],
                        'header'=>[

                        ]
                    ]
                );
                $res = json_decode((string)$result->getBody());
                $imagekit_id = $res->fileId;
                $file->imagekit_id = $imagekit_id;
                $file->url = $res->url;
                $file->update();
                self::moveToAws($file);
                return $file;
            } else {
                $file->cloudinary_id = '-1';
                $file->update();
                return false;
            }
    }

    public static function moveToAws($file){
        $path = $file->path;
        if($file->disk === 'local'){
           $res = Storage::disk('s3')->put($path, Storage::get($path));
           if($res){
               $file->disk = 's3';
               $file->update();
               $arr = explode('/',$file->url);
               $new_path = $arr[count($arr) - 2].'/'.$arr[count($arr) - 1];
               $new_path = 'images/'.now()->format('Y/M/d/').$new_path;
               $res = Storage::disk('s3')->put($new_path,file_get_contents($file->url),'public');
               if($res){
                   $url = env('CLOUDFRONT_ENDPOINT').$new_path;
                   $file->aws_url = $new_path;
                   $file->url = $url;
                   $file->update();
                   Storage::delete($path);
               } else {
               }
           }

        }
    }

    /**
     * delete file
     */
    public static function delete($file){
        Storage::disk($file->disk)->delete($file->path);
        return $file->delete();
    }

    public static function sanitizeFile($file){

    }
}
