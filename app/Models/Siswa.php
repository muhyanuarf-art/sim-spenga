<?php

namespace App\Models;

use App\Support\KonteksPeriode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    // kelas_id TIDAK ada lagi di sini: keanggotaan kelas pindah ke tabel
    // anggota_kelas karena satu siswa punya kelas BERBEDA tiap semester.
    // Lihat App\Models\AnggotaKelas.
    protected $fillable = ['nis', 'nisn', 'nama', 'nama_ortu', 'no_wa_ortu', 'jenis_kelamin', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Orang tua login memakai NIS anaknya (lihat OrangTua & guard
     * 'orangtua'), dan NIS itu DISALIN ke tabel orang_tuas saat akun
     * dibuat. Kalau NIS siswa dikoreksi di menu Data Siswa — hal yang wajar
     * terjadi — salinannya jadi basi dan orang tua TIDAK BISA LOGIN LAGI
     * tanpa ada pesan apa pun yang menjelaskan kenapa.
     *
     * Disinkronkan lewat event model (bukan di controller) supaya berlaku
     * untuk SEMUA jalur perubahan: form Edit, Import Excel, maupun tinker.
     */
    protected static function booted(): void
    {
        static::updated(function (self $siswa) {
            if ($siswa->wasChanged('nis')) {
                OrangTua::where('siswa_id', $siswa->id)->update(['nis' => $siswa->nis]);
            }
        });
    }

    /** Seluruh keanggotaan kelas siswa ini, lintas semester. */
    public function keanggotaanKelas(): HasMany
    {
        return $this->hasMany(AnggotaKelas::class, 'siswa_id');
    }

    /**
     * KELAS SISWA INI PADA PERIODE YANG SEDANG DILIHAT.
     *
     * Bentuknya sengaja tetap "satu relasi yang menghasilkan Kelas",
     * supaya seluruh pemakaian lama ($siswa->kelas->nama_kelas,
     * with('kelas'), kop lembar cetak) tidak perlu diubah.
     */
    public function kelas(): HasOneThrough
    {
        $periode = KonteksPeriode::pilihan();

        return $this->hasOneThrough(
            Kelas::class,
            AnggotaKelas::class,
            'siswa_id',  // kunci di anggota_kelas yang menunjuk siswa
            'id',        // kunci di kelas
            'id',        // kunci lokal di siswas
            'kelas_id'   // kunci di anggota_kelas yang menunjuk kelas
        )->when(
            $periode,
            fn ($q) => $q->where('anggota_kelas.tahun_ajaran_id', $periode->id),
            fn ($q) => $q->whereRaw('1 = 0')
        );
    }

    /** Kelas siswa ini pada SEMESTER tertentu (dipakai laporan periode lampau). */
    public function kelasPada(?TahunAjaran $periode): ?Kelas
    {
        if (! $periode) {
            return null;
        }

        return AnggotaKelas::where('tahun_ajaran_id', $periode->id)
            ->where('siswa_id', $this->id)->first()?->kelas;
    }

    /** id kelas siswa ini pada periode yang sedang dilihat — pengganti $siswa->kelas_id. */
    public function kelasIdSekarang(): ?int
    {
        return $this->kelas?->id;
    }

    /** Penyaring "siswa di kelas ini" — pengganti where('kelas_id', ...). */
    public function scopeDiKelas($query, $kelasId)
    {
        return $query->whereHas('keanggotaanKelas', fn ($q) => $q->where('kelas_id', $kelasId));
    }

    /** Penyaring "siswa di salah satu kelas ini". */
    public function scopeDiKelasIn($query, $kelasIds)
    {
        return $query->whereHas('keanggotaanKelas', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class, 'siswa_id');
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'siswa_id');
    }

    // ==== Modul BK ====
    public function kasusBk(): HasMany { return $this->hasMany(KasusSiswa::class, 'siswa_id'); }

    /**
     * Siswa yang punya kasus BK yang BELUM SELESAI.
     *
     * Dipakai tiga halaman pencatatan BK — Catat Pembinaan, Pengurangan
     * Poin, dan Pemanggilan Orang Tua — untuk membatasi siswa yang boleh
     * dipilih.
     *
     * Alasannya bukan sekadar memperpendek daftar, melainkan menyambung
     * alurnya: ketiga pencatatan itu adalah TINDAK LANJUT atas sebuah
     * kasus. Selama dulu daftarnya berisi seluruh siswa, pengguna bisa
     * mencatat pembinaan untuk siswa yang tidak punya perkara apa pun —
     * catatan yang lalu menggantung tanpa asal-usul, dan tidak pernah
     * menutup kasus mana pun.
     *
     * Yang dihitung "belum selesai" adalah kasus yang belum dibatalkan
     * DAN statusnya belum "Selesai" — jadi mencakup kasus yang masih
     * "Baru" maupun yang sudah "Dalam Pembinaan" (pembinaan lanjutan
     * memang wajar untuk perkara yang sedang berjalan).
     */
    public function scopePunyaKasusTerbuka($query)
    {
        return $query->whereHas('kasusBk', fn ($q) => $q->belumSelesai());
    }
    public function pembinaanBk(): HasMany { return $this->hasMany(PembinaanSiswa::class, 'siswa_id'); }
    public function penguranganPoinBk(): HasMany { return $this->hasMany(PenguranganPoinSiswa::class, 'siswa_id'); }
    public function pemanggilanOrtuBk(): HasMany { return $this->hasMany(PemanggilanOrangTua::class, 'siswa_id'); }

    /**
     * Riwayat mutasi kelas (awal masuk, kenaikan kelas antar tahun ajaran,
     * maupun pindah kelas di tengah tahun ajaran berjalan), diurutkan dari
     * periode paling awal berdasarkan tanggal efektif mutasinya.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'siswa_id')->orderBy('tanggal_mutasi')->orderBy('id');
    }

    /**
     * SISWA YANG TERCATAT PADA SATU TAHUN AJARAN.
     *
     * Tabel siswas sengaja TIDAK diberi kolom tahun_ajaran_id: seorang
     * siswa adalah satu orang yang sama dari kelas 7 sampai 9 (NIS-nya
     * unik, akun orang tuanya menempel di situ, riwayat BK & nilainya
     * menunjuk baris yang sama). Yang berganti tiap tahun adalah KELASNYA,
     * dan kelas SUDAH terikat tahun ajaran.
     *
     * Jadi "siswa periode ini" = siswa yang kelasnya milik periode ini.
     * Konsekuensinya persis seperti yang diminta sekolah: begitu tahun
     * ajaran baru diaktifkan dan siswa kelas 7–8 dipindahkan ke kelas
     * barunya (lewat Import Excel Data Siswa), siswa kelas 9 yang lulus
     * TIDAK ikut berpindah sehingga otomatis berhenti muncul di seluruh
     * menu — tanpa perlu dinonaktifkan satu per satu.
     *
     * Halaman histori (riwayat kelas, laporan BK periode lama, portal
     * orang tua) sengaja TIDAK memakai scope ini supaya data lama tetap
     * bisa dibuka.
     */
    public function scopeUntukTahunAjaran($query, ?TahunAjaran $tahunAjaran)
    {
        if (! $tahunAjaran) {
            return $query;
        }

        return $query->whereHas(
            'keanggotaanKelas',
            fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id)
        );
    }

    /** Siswa pada periode yang sedang aktif — dipakai semua daftar & pencarian siswa. */
    public function scopePeriodeAktif($query)
    {
        return $query->untukTahunAjaran(KonteksPeriode::pilihan());
    }
}
