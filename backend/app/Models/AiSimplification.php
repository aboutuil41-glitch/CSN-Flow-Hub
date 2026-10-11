<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSimplification extends Model
{
    protected $fillable = [
        'feedback_id',
        'simplified_feedback',
        'sentiment',
        'priority',
        'suggested_action'
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }
}