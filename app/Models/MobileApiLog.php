<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileApiLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'received_at' => 'immutable_datetime',
            'request_body' => 'array',
            'response_body' => 'array',
            'client_metadata' => 'array',
        ];
    }
}
