<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number', 'pelapor_nama', 'ruangan', 'keluhan', 
        'urgency', 'urgency_reason', 'status', 'tindakan_teknisi', 'room_id','needs_procurement',
    'procurement_items_request',
    'procurement_status',
    ];

    protected $casts = [
        'procurement_items_request' => 'array',
        'needs_procurement' => 'boolean',
    ];

    // Relasi ke User (Pelapor)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke tabel Procurement
    public function procurement()
    {
        return $this->hasOne(Procurement::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $dateCode = date('Ymd');
            $countToday = static::whereDate('created_at', now())->count();
            $sequence = str_pad($countToday + 1, 2, '0', STR_PAD_LEFT);
            $random = strtoupper(Str::random(4));
            $model->ticket_number = "REP-{$dateCode}-{$random} {$sequence}";
        });
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'Belum Diproses' => 'bg-yellow-100 text-yellow-800',
            'Diproses'       => 'bg-blue-100 text-blue-800',
            'Selesai'        => 'bg-green-100 text-green-800',
            'Tidak Selesai'  => 'bg-red-600 text-white', 
            'Ditolak'        => 'bg-gray-100 text-gray-800',
            default          => 'bg-gray-100 text-gray-800',
        };
    }

}