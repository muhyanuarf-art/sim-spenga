<?php

namespace App\Http\Controllers;

use App\Models\DisposisiSurat;
use App\Models\Surat;
use App\Models\SuratActivity;
use App\Models\User;
use Illuminate\Http\Request;

class DisposisiSuratController extends Controller
{
    /** Disposisi masuk untuk user yang login. */
    public function index(Request $request)
    {
        $disposisi = DisposisiSurat::with(['surat.jenisSurat', 'surat.siswa', 'dariUser'])
            ->where('kepada_user_id', $request->user()->id)
            ->orderByRaw("FIELD(status,'menunggu','dibaca','diproses','selesai','ditolak')")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('surat.disposisi-index', compact('disposisi'));
    }

    /** Kirim disposisi baru untuk 1 surat (dari halaman detail surat). */
    public function store(Request $request, Surat $surat)
    {
        $validated = $request->validate([
            'kepada_user_id' => ['required', 'exists:users,id'],
            'instruksi' => ['nullable', 'string', 'max:1000'],
            'batas_waktu' => ['nullable', 'date'],
        ]);
        $validated['surat_id'] = $surat->id;
        $validated['dari_user_id'] = $request->user()->id;

        DisposisiSurat::create($validated);

        $penerima = User::find($validated['kepada_user_id']);
        SuratActivity::catat($surat, 'Surat didisposisikan', "Kepada {$penerima?->name}");

        return back()->with('success', 'Disposisi berhasil dikirim.');
    }

    /** Penerima menandai disposisi sudah dibaca. */
    public function baca(DisposisiSurat $disposisi)
    {
        if ($disposisi->status === 'menunggu') {
            $disposisi->update(['status' => 'dibaca', 'dibaca_at' => now()]);
            SuratActivity::catat($disposisi->surat, 'Disposisi dibaca', "Oleh {$disposisi->kepadaUser?->name}");
        }

        return back()->with('success', 'Ditandai sudah dibaca.');
    }

    /** Penerima menindaklanjuti: proses / selesai / tolak, dengan catatan. */
    public function tindakLanjut(Request $request, DisposisiSurat $disposisi)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:diproses,selesai,ditolak'],
            'catatan_penyelesaian' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($validated['status'], ['selesai', 'ditolak'])) {
            $validated['selesai_at'] = now();
        }
        if (!$disposisi->dibaca_at) {
            $validated['dibaca_at'] = now();
        }

        $disposisi->update($validated);

        $label = match ($validated['status']) {
            'diproses' => 'Surat diproses',
            'selesai' => 'Surat diselesaikan',
            'ditolak' => 'Disposisi ditolak',
        };
        $catatan = $validated['catatan_penyelesaian'] ?? null;
        $keterangan = "Oleh {$disposisi->kepadaUser?->name}" . ($catatan ? ": {$catatan}" : '');
        SuratActivity::catat($disposisi->surat, $label, $keterangan);

        return back()->with('success', 'Status disposisi berhasil diperbarui.');
    }

    /** Daftar user untuk dropdown "kirim ke" — dipakai form di surat/show. */
    public static function calonPenerima()
    {
        return User::where('is_active', true)
            ->whereIn('role', ['guru', 'guru_bk', 'kesiswaan', 'tu', 'kurikulum', 'kepala_sekolah', 'admin'])
            ->orderBy('name')->get();
    }
}
