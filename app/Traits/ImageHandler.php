<?php

namespace App\Traits;

use App\Support\Media;

trait ImageHandler
{
    public function storeImage($request, $folder,$filename)
    {
            $file = $request->file($filename);
            if (! $file) {
                return null;
            }

            return Media::put($file, 'images/'.$folder);

    }

    public function updateImage($request, $model,$folder)
{
    // Check if the model has the old photo
        $this->deleteImage($model);
        return $this->storeImage($request, $folder);
}


    public function deleteImage($model)
    {

        if ($model->logo) {
            Media::delete($model->logo);
        }
    }
}

