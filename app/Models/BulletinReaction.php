<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinReaction extends Model
{
    protected $fillable = ['bulletin_id', 'user_id', 'emoji'];

    public function bulletin()
    {
        return $this->belongsTo(CompanyBulletin::class, 'bulletin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
