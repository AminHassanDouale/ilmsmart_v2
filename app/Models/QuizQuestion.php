<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'question', 'type', 'points', 'order', 'explanation'];

    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function answers() { return $this->hasMany(QuizAnswer::class, 'question_id')->orderBy('order'); }

    public function correctAnswer()
    {
        return $this->hasOne(QuizAnswer::class, 'question_id')->where('is_correct', true);
    }
}
