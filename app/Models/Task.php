<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;


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
        //Get the current user id using JWT if available, fallback to Auth
        $user = null;
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Throwable $e) {
            // ignore and fallback
        }
        $userId = $user ? (is_object($user) && property_exists($user, 'id') ? $user->id : (method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : null)) : Auth::id();
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
