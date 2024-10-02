<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AktivitasPelaksana extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer',
        'pelaksana' => 'integer',
        'status_aktivitas' => 'integer',
        'kelurahan' => 'integer',
        'rw' => 'integer',
    ];

    /**
     * Get the pelaksana_users that owns the AktivitasPelaksana
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pelaksana_users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelaksana', 'id');
    }

    /**
     * Get the status_aktivitas that owns the AktivitasPelaksana
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status_aktivitas(): BelongsTo
    {
        return $this->belongsTo(StatusAktivitas::class, 'status_aktivitas', 'id');
    }

    /**
     * Get the kelurahans that owns the AktivitasPelaksana
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kelurahans(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan', 'id');
    }
}
