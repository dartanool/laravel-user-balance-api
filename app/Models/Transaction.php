<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'comment', 'related_user'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
