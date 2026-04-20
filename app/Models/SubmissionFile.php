<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $fillable = ['submission_id', 'file_path', 'file_name', 'file_type', 'file_size'];

    public function submission() { return $this->belongsTo(AssignmentSubmission::class, 'submission_id'); }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
