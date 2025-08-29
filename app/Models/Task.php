<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


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


    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->orWhereHas('users', fn($q) => $q->where('users.id', $userId));
    }

    public function scopeByStatus(Builder $query, int $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, int $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStartDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('startDate', $date);
    }

    public function scopeByStartDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('startDate', [$from, $to]);
    }

    public function scopeOrderByField(Builder $query, string $field, string $direction = 'asc'): Builder
    {
        $allowedFields = ['startDate', 'endDate', 'priority', 'created_at', 'title'];
        $allowedDir = ['asc', 'desc'];

        if (!in_array($field, $allowedFields, true)) return $query;
        if (!in_array($direction, $allowedDir, true)) return $query;

        return $query->orderBy($field, $direction);
    }
}
