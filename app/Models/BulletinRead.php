<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinRead extends Model
{
    protected $table = 'bulletin_reads';

    protected $fillable = ['bulletin_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function bulletin()
    {
        return $this->belongsTo(CompanyBulletin::class, 'bulletin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
