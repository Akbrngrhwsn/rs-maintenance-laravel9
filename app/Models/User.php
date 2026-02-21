<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Helper untuk mengecek role kepala ruang
     */
    public function isKepalaRuang()
    {
        return $this->role === 'kepala_ruang';
    }

    /**
     * Relasi ke Room: Seorang User (Kepala Ruang) mengepalai satu ruangan
     */
    public function room()
    {
        // Ubah 'manaid' menjadi 'kepala_ruang_id'
        return $this->hasOne(Room::class, 'kepala_ruang_id');
    }
}