<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Evidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_id', 'file_path', 'file_name', 'file_type',
        'original_name', 'file_size', 'description', 'mime_type', 'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean'
    ];

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

    public function getFileUrl()
    {
        return Storage::url($this->file_path);
    }

    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument()
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
