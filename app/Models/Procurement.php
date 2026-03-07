<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    protected $fillable = [
    'report_id', 
    'app_request_id',
    'items', 
    'total',
    'status', 
    'director_note', 
    'qr_kepala_ruang', 
    'qr_management', 
    'qr_bendahara', 
    'qr_direktur'];

    // Mengubah JSON 'items' menjadi array
    protected $casts = [
        'items' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    // Human-readable label for status, localized to Indonesian
    public function getStatusLabelAttribute()
    {
        $map = [
            'submitted_to_kepala_ruang' => 'Menunggu Konfirmasi Kepala Ruang',
            'submitted_to_management'   => 'Menunggu Konfirmasi Management',
            'submitted_to_bendahara' => 'Menunggu Konfirmasi Bendahara',
            'submitted_to_director' => 'Menunggu ACC Direktur',
            'approved_by_director' => 'Disetujui',
            'rejected' => 'Ditolak',
            'draft' => 'Draft',
            'pending' => 'Menunggu',
        ];

        return $map[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    
}