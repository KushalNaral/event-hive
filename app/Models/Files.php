<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    use HasFactory;

    protected $table = "files";

    protected $fillable = [
        'model_id', 'model_type', 'location', 'type', 'url', 'name', 'extension', 'size', 'download',
        'created_by', 'updated_by', 'deleted_by'
    ];
}
