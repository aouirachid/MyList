<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'document_id',
        'title',
        'description',
        'startDate',
        'endDate',
        'priority',
        'parentTaskId',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'collaborators', 'task_id', 'user_id')->withTimestamps();
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task__tags');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    public static function getAllTasks()
    {
        //Get the current user id
        $userId = Auth::id();
        //Get the tasks for the current user and the tasks for the current user's collaborators
        return self::with(['users', 'tags', 'document'])
        //Get the tasks for the current user
            ->where('user_id', $userId)
            //Get the tasks for the current user's collaborators
            ->orWhereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->get();
    }

}