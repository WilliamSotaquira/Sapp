<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskGitAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'repository_url',
        'branch_name',
        'commit_hash',
        'pull_request_url',
        'pr_status',
        'commit_message',
    ];

    // Relaciones
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
