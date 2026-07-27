<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadController extends BaseApiController
{
    /*
    |--------------------------------------------------------------------------
    | Upload Single Image
    |--------------------------------------------------------------------------
    */

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'folder' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(),422);
        }

        $folder = $request->folder ?? 'uploads/images';

        $file = $request->file('image');

        $filename = time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs($folder,$filename,'public');

        return $this->success([
            'file_name'=>$filename,
            'path'=>$path,
            'url'=>asset('storage/'.$path),
        ],'Image uploaded successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Multiple Images
    |--------------------------------------------------------------------------
    */

    public function uploadMultipleImages(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'images'=>'required|array',
            'images.*'=>'image|max:10240'
        ]);

        if($validator->fails()){
            return $this->error($validator->errors()->first(),422);
        }

        $folder = $request->folder ?? 'uploads/images';

        $files=[];

        foreach($request->file('images') as $image){

            $filename=time().'_'.Str::random(8).'.'.$image->getClientOriginalExtension();

            $path=$image->storeAs($folder,$filename,'public');

            $files[]=[
                'name'=>$filename,
                'path'=>$path,
                'url'=>asset('storage/'.$path)
            ];
        }

        return $this->success($files,'Images uploaded successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Video
    |--------------------------------------------------------------------------
    */

    public function uploadVideo(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'video'=>'required|mimes:mp4,mov,avi,mkv|max:102400'
        ]);

        if($validator->fails()){
            return $this->error($validator->errors()->first(),422);
        }

        $folder=$request->folder ?? 'uploads/videos';

        $file=$request->file('video');

        $filename=time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

        $path=$file->storeAs($folder,$filename,'public');

        return $this->success([
            'file_name'=>$filename,
            'path'=>$path,
            'url'=>asset('storage/'.$path)
        ],'Video uploaded successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Document
    |--------------------------------------------------------------------------
    */

    public function uploadDocument(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'document'=>'required|max:20480'
        ]);

        if($validator->fails()){
            return $this->error($validator->errors()->first(),422);
        }

        $folder=$request->folder ?? 'uploads/documents';

        $file=$request->file('document');

        $filename=time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

        $path=$file->storeAs($folder,$filename,'public');

        return $this->success([
            'file_name'=>$filename,
            'path'=>$path,
            'url'=>asset('storage/'.$path)
        ],'Document uploaded successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete File
    |--------------------------------------------------------------------------
    */

    public function delete(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'path'=>'required|string'
        ]);

        if($validator->fails()){
            return $this->error($validator->errors()->first(),422);
        }

        if(Storage::disk('public')->exists($request->path)){
            Storage::disk('public')->delete($request->path);

            return $this->success(null,'File deleted successfully');
        }

        return $this->error('File not found',404);
    }

    /*
    |--------------------------------------------------------------------------
    | Replace File
    |--------------------------------------------------------------------------
    */

    public function replace(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'old_path'=>'required|string',
            'file'=>'required|max:102400'
        ]);

        if($validator->fails()){
            return $this->error($validator->errors()->first(),422);
        }

        if(Storage::disk('public')->exists($request->old_path)){
            Storage::disk('public')->delete($request->old_path);
        }

        $folder=dirname($request->old_path);

        $file=$request->file('file');

        $filename=time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

        $path=$file->storeAs($folder,$filename,'public');

        return $this->success([
            'file_name'=>$filename,
            'path'=>$path,
            'url'=>asset('storage/'.$path)
        ],'File replaced successfully');
    }
}