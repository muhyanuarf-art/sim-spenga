<?php

namespace App\Http\Controllers;

use App\Jobs\KirimPengingatJurnalWhatsapp;
use App\Models\PengaturanNotifikasiGuru;
use App\Models\PengingatJurnal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Halaman Pengaturan → "Pengingat Guru (WhatsApp)".
 *
 * Di sinilah Admin menyalakan/mematikan pengingat, mengatur jedanya,
 * memasukkan token perangkat kedua (nomor kepala sekolah), mengubah naskah
 * pesannya, dan mengirim pesan uji coba sebelum dipakai sungguhan.
 *
 * Halaman ini SENGAJA terpisah dari Pengaturan Sekolah. Pengaturan Sekolah
 * berisi identitas yang dibaca hampir setiap halaman lewat view composer
 * global; menaruh token rahasia di sana berarti token ikut terbawa ke
 * seluruh tampilan tanpa pernah dibutuhkan di sana.
 */
class PengaturanNotifikasiController extends Controller
{
    public function edit(Request $request)
    {
        $pengaturan = PengaturanNotifikasiGuru::current();

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $awal = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth()->endOfDay();

        // Riwayat dipakai admin untuk memastikan fitur ini benar-benar
        // bekerja — tanpa itu, "sudah dinyalakan" dan "benar-benar terkirim"
        // tidak bisa dibedakan.
        $riwayat = PengingatJurnal::with(['guru', 'kelas', 'mapel'])
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $ringkasan = PengingatJurnal::whereBetween('tanggal', [$awal, $akhir])
            ->selectRaw('status_kirim, COUNT(*) AS jumlah')
            ->groupBy('status_kirim')
            ->pluck('jumlah', 'status_kirim');

        return view('pengaturan-notifikasi.edit', compact(
            'pengaturan', 'riwayat', 'ringkasan', 'bulan', 'tahun'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'jeda_menit' => ['required', 'integer', 'min:5', 'max:240'],
            'jam_mulai_kirim' => ['required', 'date_format:H:i'],
            'jam_akhir_kirim' => ['required', 'date_format:H:i', 'after:jam_mulai_kirim'],
            'fonnte_token' => ['nullable', 'string', 'max:255'],
            'template_pesan' => ['nullable', 'string', 'max:2000'],
        ], [
            'jeda_menit.min' => 'Jeda minimal 5 menit — terlalu cepat akan mengganggu guru yang baru selesai mengajar.',
            'jeda_menit.max' => 'Jeda maksimal 240 menit (4 jam).',
            'jam_akhir_kirim.after' => 'Jam akhir kirim harus lebih malam daripada jam mulai.',
        ]);

        $pengaturan = PengaturanNotifikasiGuru::current();

        $data = [
            'aktif' => $request->boolean('aktif'),
            'jeda_menit' => $validated['jeda_menit'],
            'jam_mulai_kirim' => $validated['jam_mulai_kirim'],
            'jam_akhir_kirim' => $validated['jam_akhir_kirim'],
            // `?? null` bukan sekadar kehati-hatian: aturan `nullable`
            // membuat kunci ini TIDAK ADA sama sekali di hasil validasi bila
            // formulir tidak mengirimkannya, dan mengambilnya langsung akan
            // menggagalkan seluruh penyimpanan.
            'template_pesan' => ($validated['template_pesan'] ?? null) ?: null,
        ];

        // Kolom token dikosongkan artinya "biarkan token yang sekarang",
        // BUKAN "hapus tokennya". Kalau tidak begitu, admin yang membuka
        // halaman ini hanya untuk mengubah jeda akan tanpa sadar menghapus
        // token — karena isian sandi memang tidak pernah ditampilkan lagi.
        // Untuk benar-benar menghapus, ada tombol tersendiri di bawah.
        $tokenBaru = trim((string) ($validated['fonnte_token'] ?? ''));

        if ($tokenBaru !== '') {
            $data['fonnte_token'] = $tokenBaru;
        }

        $pengaturan->update($data);
        PengaturanNotifikasiGuru::lupakanCache();

        $pesan = 'Pengaturan pengingat berhasil disimpan.';

        if ($data['aktif'] && ! $pengaturan->fresh()->token()) {
            $pesan .= ' Catatan: pengingat sudah dinyalakan tetapi token perangkat WhatsApp kepala sekolah belum diisi, jadi belum ada pesan yang bisa terkirim.';
        }

        return back()->with('success', $pesan);
    }

    /** Hapus token yang tersimpan (mis. perangkat kepala sekolah diganti). */
    public function hapusToken()
    {
        $pengaturan = PengaturanNotifikasiGuru::current();
        $pengaturan->update(['fonnte_token' => null, 'aktif' => false]);
        PengaturanNotifikasiGuru::lupakanCache();

        return back()->with('success', 'Token perangkat dihapus dan pengingat dimatikan. Isi token baru lalu nyalakan lagi bila perlu.');
    }

    /**
     * Kirim satu pesan uji ke nomor yang diketik admin, memakai perangkat
     * kedua — sekaligus membuktikan tokennya benar SEBELUM fitur ini
     * dinyalakan untuk seluruh guru.
     *
     * Dikirim LANGSUNG (tidak lewat antrian) karena admin sedang menunggu
     * jawabannya di layar: kalau lewat antrian, halaman hanya bisa berkata
     * "sudah dimasukkan ke antrian", yang tidak membuktikan apa pun.
     */
    public function uji(Request $request)
    {
        $validated = $request->validate([
            'nomor_uji' => ['required', 'string', 'max:25'],
        ], [
            'nomor_uji.required' => 'Isi dulu nomor WhatsApp tujuan uji coba.',
        ]);

        $pengaturan = PengaturanNotifikasiGuru::current();
        $token = $pengaturan->token();

        if (! $token) {
            return back()->with('error', 'Token perangkat WhatsApp kepala sekolah belum diisi. Simpan tokennya lebih dulu, baru kirim uji coba.');
        }

        $nomor = preg_replace('/[^0-9]/', '', $validated['nomor_uji']) ?? '';

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        if (strlen($nomor) < 9) {
            return back()->with('error', 'Nomor WhatsApp tujuan uji coba tidak masuk akal. Contoh yang benar: 081234567890.');
        }

        $pesan = "Uji coba pengingat jurnal & absensi dari "
            .(\App\Models\PengaturanSekolah::current()->nama_sekolah ?: 'SIM-SPENGA').".\n\n"
            ."Bila pesan ini sampai, berarti perangkat WhatsApp kepala sekolah sudah tersambung dengan benar "
            ."dan pengingat siap dinyalakan.\n\n"
            ."_Dikirim ".now()->translatedFormat('l, d F Y H:i')."_";

        try {
            $response = Http::timeout(20)
                ->asForm()
                ->withHeaders(['Authorization' => $token])
                ->post(config('services.fonnte.url'), [
                    'target' => $nomor,
                    'message' => $pesan,
                ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Tidak bisa terhubung ke Fonnte: '.$e->getMessage());
        }

        $body = $response->json() ?? [];

        if ($response->successful() && (($body['status'] ?? false) === true)) {
            return back()->with('success', "Pesan uji berhasil dikirim ke {$nomor}. Periksa WhatsApp nomor tersebut.");
        }

        $alasan = $body['reason'] ?? ('HTTP '.$response->status().': '.substr($response->body(), 0, 200));

        return back()->with('error', "Fonnte menolak pesan uji: {$alasan}");
    }

    /**
     * Kirim ulang satu pengingat yang gagal. Dipakai admin setelah nomor
     * guru yang salah diperbaiki di menu Kelola Pengguna.
     */
    public function kirimUlang(PengingatJurnal $pengingat)
    {
        if (! in_array($pengingat->status_kirim, ['gagal', 'dilewati', 'kedaluwarsa'], true)) {
            return back()->with('error', 'Hanya pengingat berstatus Gagal, Dilewati, atau Kedaluwarsa yang bisa dikirim ulang.');
        }

        // Pengingat hari lampau tidak akan pernah jadi dikirim (lihat
        // KirimPengingatJurnalWhatsapp::kadaluwarsa). Ditolak di sini juga
        // supaya Admin mendapat jawaban yang jujur seketika, bukan melihat
        // barisnya berkedip ke 'Menunggu' lalu balik lagi jadi 'Kedaluwarsa'
        // beberapa detik kemudian tanpa penjelasan.
        if (! $pengingat->tanggal->isToday()) {
            return back()->with('error',
                'Pengingat ini untuk '.$pengingat->tanggal->translatedFormat('l, d F Y')
                .' dan hari itu sudah lewat. Pengingat hanya dikirim pada hari mengajarnya —'
                .' menegur guru keesokan harinya justru membuat pengingat ini diabaikan.');
        }

        $pengingat->update([
            'status_kirim' => 'pending',
            'percobaan_ke' => 1,
            'keterangan_gagal' => null,
        ]);

        KirimPengingatJurnalWhatsapp::dispatch($pengingat->id);

        return back()->with('success', 'Pengingat dimasukkan lagi ke antrian pengiriman.');
    }
}
