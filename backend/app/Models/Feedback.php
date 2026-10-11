<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'message',
        'type',
    ];

    public function files()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiSimplification()
    {
        return $this->hasOne(AiSimplification::class);
    }
}
