<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalMengajarSlot extends Model
{
    protected $table = 'jurnal_mengajar_slots';

    protected $fillable = [
        'jurnal_mengajar_id', 'jadwal_pelajaran_id', 'tanggal',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function jurnal(): BelongsTo { return $this->belongsTo(JurnalMengajar::class, 'jurnal_mengajar_id'); }
    public function jadwal(): BelongsTo { return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id'); }
}
