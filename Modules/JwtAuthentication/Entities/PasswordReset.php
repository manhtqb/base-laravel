<?php

namespace Modules\JwtAuthentication\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordReset extends Model
{
    use HasFactory;
    const UPDATED_AT = null;
    protected $fillable = [
        'email', 'token'
    ];
}
