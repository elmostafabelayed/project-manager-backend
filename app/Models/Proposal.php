<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function freelancer() {
        return $this->belongsTo(User::class, 'freelancer_id');
    }
}
