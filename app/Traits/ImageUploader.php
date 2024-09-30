<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Files;

trait ImageUploader
{
    public function handleFileUpload(UploadedFile $file, string $directory, string $modelType, int $modelId)
    {

        //dd($file, $directory, $modelType, $modelId);

        $path = $file->store($directory, 'public');

        $fileModel = new Files();
        $fileModel->model_id = $modelId;
        $fileModel->model_type = $modelType;
        $fileModel->location = $path;
        $fileModel->type = $file->getMimeType();
        $fileModel->url = Storage::url($path);
        $fileModel->name = $file->getClientOriginalName();
        $fileModel->extension = $file->getClientOriginalExtension();
        $fileModel->size = $file->getSize();
        $fileModel->created_by = auth()->id();
        $fileModel->save();

        return $fileModel;
    }

    public function updateFile(Files $fileModel, UploadedFile $newFile, string $directory)
    {
        if (Storage::disk('public')->exists($fileModel->location)) {
            Storage::disk('public')->delete($fileModel->location);
        }

        $path = $newFile->store($directory, 'public');

        $fileModel->location = $path;
        $fileModel->type = $newFile->getMimeType();
        $fileModel->url = Storage::url($path);
        $fileModel->name = $newFile->getClientOriginalName();
        $fileModel->extension = $newFile->getClientOriginalExtension();
        $fileModel->size = $newFile->getSize();
        $fileModel->updated_by = auth()->id();
        $fileModel->save();

        return $fileModel;
    }

    public function deleteFile(File $file): bool
    {
        if (Storage::disk('public')->exists($file->location)) {
            Storage::disk('public')->delete($file->location);
        }

        return $file->delete();
    }

}

