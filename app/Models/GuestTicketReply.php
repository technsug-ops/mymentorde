<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class GuestTicketReply extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'guest_ticket_id',
        'author_role',
        'author_email',
        'message',
        'attachment_path',
        'attachment_name',
    ];

    public function ticket()
    {
        return $this->belongsTo(GuestTicket::class, 'guest_ticket_id');
    }
}

