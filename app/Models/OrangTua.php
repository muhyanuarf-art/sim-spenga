<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun login untuk orang tua/wali siswa. Guard terpisah ('orangtua'),
 * lihat config/auth.php. Satu akun = satu siswa, login pakai NIS.
 */
class OrangTua extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Password default akun baru (di-hash otomatis lewat cast 'hashed'). Wajib diganti orang tua setelah login pertama. */
    public const PASSWORD_DEFAULT = 'password';

    protected $table = 'orang_tuas';

    protected $fillable = [
        'siswa_id', 'nis', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_diubah_at' => 'datetime',
        ];
    }

    /** Dipakai Auth::attempt() sebagai kolom "username". */
    public function username(): string
    {
        return 'nis';
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
