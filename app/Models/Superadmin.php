<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Superadmin extends Authenticatable
{
    use HasApiTokens, HasPrefixedId;

    const UPDATED_AT = null;

    protected $fillable = ['name', 'email', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function idPrefix(): string
    {
        return 'sa';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function shops()
    {
        return $this->hasMany(Shop::class, 'created_by');
    }
}
