<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRevision extends Model
{
    protected $fillable = ['document_id', 'author_name', 'content'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
