<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsToMany(User::class);
    }
    public function tag()
    {
        return $this->belongsToMany(Tag::class);
    }
    
   public function document()
   {
    return $this->belongsTo(Document::class);
   }
}

