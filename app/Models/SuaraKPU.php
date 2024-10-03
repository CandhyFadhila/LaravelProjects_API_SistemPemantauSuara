<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuaraKPU extends Model
{
    use HasFactory;

    protected $table = 'suara_kpus';
    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer',
        'partai_id' => 'integer',
        'kelurahan_id' => 'integer',
        'tps' => 'integer',
        'jumlah_suara' => 'integer',
        'jumlah_dpt' => 'integer',
    ];

    /**
     * Get the partais that owns the SuaraKPU
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partais(): BelongsTo
    {
        return $this->belongsTo(Partai::class, 'partai_id', 'id');
    }

    /**
     * Get the kelurahans that owns the SuaraKPU
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kelurahans(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id', 'id');
    }
}
