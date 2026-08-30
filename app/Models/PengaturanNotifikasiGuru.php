<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan pengingat jurnal & absensi untuk guru — baris tunggal,
 * diisi Admin lewat menu Pengaturan → Pengingat Guru (WhatsApp).
 *
 * PEMISAHAN DUA PERANGKAT FONNTE
 * ------------------------------
 * Sekolah memakai dua nomor WhatsApp yang berbeda, dan keduanya sengaja
 * tidak boleh tertukar:
 *
 *   Perangkat 1 — nomor sekolah.
 *     Mengirim pemberitahuan siswa Alfa kepada ORANG TUA.
 *     Tokennya dari .env (`FONNTE_TOKEN`), dibaca lewat
 *     `config('services.fonnte.token')`. Tidak disentuh kelas ini.
 *
 *   Perangkat 2 — nomor kepala sekolah.
 *     Mengirim pengingat jurnal & absensi kepada GURU.
 *     Tokennya diisi Admin di halaman Pengaturan dan disimpan di sini.
 *
 * Dengan begitu guru menerima pengingat dari nomor kepala sekolah — bukan
 * dari nomor yang biasa menghubungi orang tua — dan riwayat percakapan
 * kedua keperluan itu tidak bercampur di satu nomor.
 */
class PengaturanNotifikasiGuru extends Model
{
    protected $table = 'pengaturan_notifikasi_gurus';

    protected $fillable = [
        'aktif',
        'jeda_menit',
        'fonnte_token',
        'jam_mulai_kirim',
        'jam_akhir_kirim',
        'template_pesan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'jeda_menit' => 'integer',
            // Token adalah rahasia yang setara kata sandi: siapa pun yang
            // memegangnya bisa mengirim WhatsApp atas nama kepala sekolah.
            // Cast 'encrypted' membuat isinya di database berupa teks acak.
            'fonnte_token' => 'encrypted',
        ];
    }

    /** Naskah bawaan bila admin belum menulis naskahnya sendiri. */
    public const TEMPLATE_BAWAAN = <<<'TXT'
Assalamu'alaikum, Bapak/Ibu {guru}.

Sistem mencatat jurnal mengajar & absensi berikut belum terisi:

Hari/Tanggal : {tanggal}
Kelas : *{kelas}*
Mata pelajaran : *{mapel}*
Jam ke : *{jam}* ({waktu})

Mohon segera diisi melalui menu "Absensi & Jurnal Mengajar" di {aplikasi}.

_Pesan otomatis dari {sekolah}. Bila jurnal sudah diisi, abaikan pesan ini._
TXT;

    /** Kata kunci yang boleh dipakai admin di dalam naskah pesan. */
    public const KATA_KUNCI = [
        '{guru}' => 'Nama guru yang diingatkan',
        '{tanggal}' => 'Hari dan tanggal, contoh: Senin, 01 September 2026',
        '{kelas}' => 'Nama kelas, contoh: 7A',
        '{mapel}' => 'Nama mata pelajaran',
        '{jam}' => 'Jam ke berapa, contoh: 3-4',
        '{waktu}' => 'Rentang pukulnya, contoh: 08.20-09.40',
        '{sekolah}' => 'Nama sekolah dari Pengaturan Sekolah',
        '{aplikasi}' => 'Alamat aplikasi',
    ];

    /** Cache di dalam satu request. */
    protected static ?self $cached = null;

    public static function current(): self
    {
        return static::$cached ??= static::first() ?? static::create([
            'aktif' => false,
            'jeda_menit' => 30,
            'jam_mulai_kirim' => '06:30:00',
            'jam_akhir_kirim' => '18:00:00',
        ]);
    }

    public static function lupakanCache(): void
    {
        static::$cached = null;
    }

    /**
     * Token perangkat 2. Isian di Pengaturan yang diutamakan; kalau kosong,
     * jatuh ke .env (`FONNTE_TOKEN_GURU`) supaya sekolah yang lebih suka
     * menaruh rahasia di berkas .env tetap terlayani.
     */
    public function token(): ?string
    {
        $dariPengaturan = trim((string) $this->fonnte_token);

        if ($dariPengaturan !== '') {
            return $dariPengaturan;
        }

        $dariEnv = trim((string) config('services.fonnte.token_guru'));

        return $dariEnv !== '' ? $dariEnv : null;
    }

    /** Naskah pesan yang berlaku sekarang. */
    public function template(): string
    {
        $isi = trim((string) $this->template_pesan);

        return $isi !== '' ? $isi : self::TEMPLATE_BAWAAN;
    }

    /**
     * Siap kirim hanya bila saklarnya dinyalakan DAN tokennya ada.
     * Dipisah dari `aktif` supaya halaman Pengaturan bisa menjelaskan
     * bedanya: "dinyalakan tapi token belum diisi" adalah keadaan yang
     * perlu diberitahukan, bukan didiamkan.
     */
    public function siapKirim(): bool
    {
        return $this->aktif && $this->token() !== null;
    }

    /** Apakah pukul sekarang berada di dalam jendela waktu pengiriman? */
    public function didalamJamKirim(?\DateTimeInterface $waktu = null): bool
    {
        $jam = ($waktu ?? now())->format('H:i:s');

        return $jam >= $this->jam_mulai_kirim && $jam <= $this->jam_akhir_kirim;
    }
}
