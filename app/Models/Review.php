<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'reviewer_id',
        'reviewed_id',
        'project_id',
        'rating',
        'comment'
    ];
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function reviewed()
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }
}
