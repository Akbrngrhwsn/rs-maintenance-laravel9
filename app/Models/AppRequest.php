<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'requested_items' => 'array',
    ];

    // Relasi
    public function user() { return $this->belongsTo(User::class); }
    public function features() { return $this->hasMany(AppFeature::class); }

    // Hitung Progress
    public function getProgressAttribute() {
        $total = $this->features()->count();
        if ($total == 0) return 0;
        $done = $this->features()->where('is_done', true)->count();
        return round(($done / $total) * 100);
    }

    // === TAMBAHAN: Auto Generate Ticket ===
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $dateCode = date('Ymd');
            // Format: APP-20260109-01 (Urut harian)
            $countToday = static::whereDate('created_at', now())->count();
            $sequence = str_pad($countToday + 1, 2, '0', STR_PAD_LEFT);
            $model->ticket_number = "APP-{$dateCode}-{$sequence}";
        });
    }
}