<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\JadwalImport;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class JadwalController extends Controller
{
    /**
     * STEP 6 Bagian 19/20 — default menampilkan Jadwal periode AKTIF, tapi
     * admin bisa memilih periode lain (histori) lewat dropdown. Form
     * "+ Tambah Jadwal" (yang butuh Mapping Guru Mengajar sebagai sumber
     * pilihan Mapel/Guru) hanya relevan untuk periode AKTIF — lihat
     * $bisaEdit di view.
     */
    public function index(Request $request)
    {
        $periodeAktif = TahunAjaran::aktif();
        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();
        $periodeDilihat = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::find($request->integer('tahun_ajaran_id'))
            : $periodeAktif;

        $kelasId = $request->get('kelas_id');
        $kelasListPeriode = $periodeDilihat ? Kelas::untukTahunAjaran($periodeDilihat)->orderBy('nama_kelas')->get() : collect();
        $kelas = $kelasId ? Kelas::find($kelasId) : null;
        // Kalau kelas yang diminta ternyata bukan milik periode yang sedang
        // dilihat (mis. sisa query string dari periode sebelumnya), jatuh
        // kembali ke kelas pertama periode ini — supaya tidak menampilkan
        // jadwal kelas yang salah periode.
        if (! $kelas || ! $kelasListPeriode->contains('id', $kelas->id)) {
            $kelas = $kelasListPeriode->first();
        }

        $jadwal = collect();
        if ($periodeDilihat && $kelas) {
            $jadwal = JadwalPelajaran::with(['mapel', 'guru', 'jamPelajaran'])
                ->where('tahun_ajaran_id', $periodeDilihat->id)
                ->where('kelas_id', $kelas->id)
                ->get()
                ->groupBy('hari');
        }

        // STEP 5 Bagian 17/23 — untuk FORM TAMBAH (hanya berlaku di periode
        // aktif), kelas yang boleh dipilih HANYA kelas tahun ajaran aktif.
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();
        $jamPerHari = JamPelajaran::where('is_active', true)
            ->orderBy('jam_ke')
            ->get()
            ->groupBy('hari')
            ->map(fn ($items) => $items->map(fn ($j) => ['id' => $j->id, 'label' => $j->label])->values());
        $hariList = JadwalPelajaran::HARI_LIST();

        // Mapping Guru Mengajar untuk kelas & tahun ajaran AKTIF (bukan
        // periode yang sedang dilihat) — dropdown Mapel & Guru pada form
        // Jadwal Pelajaran hanya relevan saat menambah jadwal BARU, yang
        // selalu masuk ke periode aktif.
        $mengajarList = ($periodeAktif && $kelas && $periodeDilihat && $periodeDilihat->id === $periodeAktif->id)
            ? GuruMengajarKelas::with(['guru', 'mapel'])
                ->where('tahun_ajaran_id', $periodeAktif->id)
                ->where('kelas_id', $kelas->id)
                ->get()
            : collect();

        // Daftar mapel unik yang tersedia untuk kelas ini, sesuai Mapping Guru Mengajar.
        $mapelList = $mengajarList->pluck('mapel')
            ->filter()
            ->unique('id')
            ->sortBy('nama_mapel')
            ->values();

        // Jaga-jaga: kalau ada jadwal lama yang mapel/gurunya sudah tidak ada lagi di mapping
        // (mis. mapping-nya diubah/dihapus belakangan), tetap sertakan supaya data lama tidak "hilang" dari tampilan.
        if ($jadwal->isNotEmpty()) {
            $mapelTerpakai = $jadwal->flatten()->pluck('mapel')->filter();
            $mapelList = $mapelList->concat($mapelTerpakai)->unique('id')->sortBy('nama_mapel')->values();
        }

        // Data mapping mapel -> guru, dikirim ke Alpine.js untuk memfilter dropdown Guru secara dinamis
        // begitu dropdown Mapel dipilih.
        $mengajarMap = $mengajarList->map(fn ($m) => [
            'mapel_id' => $m->mata_pelajaran_id,
            'guru_id' => $m->guru_id,
            'guru_nama' => $m->guru->name ?? '-',
        ])->values();

        return view('jadwal.index', compact(
            'jadwal', 'kelas', 'kelasList', 'kelasListPeriode', 'jamPerHari', 'hariList', 'mapelList', 'mengajarMap',
            'periodeAktif', 'periodeDilihat', 'tahunAjaranList'
        ));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'hari' => ['required', Rule::in(JadwalPelajaran::HARI_LIST())],
            'kelas_id' => [
                'required',
                // STEP 5 Bagian 17 — kelas WAJIB dari tahun ajaran yang sama
                // dengan jadwal ini. Ditolak kalau tidak (bukan cuma
                // disembunyikan di dropdown).
                Rule::exists('kelas', 'id')->where(
                    fn ($q) => $q->whereIn('id', Kelas::untukTahunAjaran($tahunAjaran)->pluck('id'))
                ),
            ],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'guru_id' => [
                'required',
                'exists:users,id',
                Rule::exists('guru_mengajar_kelas', 'guru_id')->where(fn ($q) => $q
                    ->where('kelas_id', $request->kelas_id)
                    ->where('mata_pelajaran_id', $request->mata_pelajaran_id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)),
            ],
            'jam_pelajaran_id' => [
                'required',
                Rule::exists('jam_pelajarans', 'id')->where(fn ($q) => $q->where('hari', $request->hari)),
                // STEP 6 Bagian 7 — KELAS bentrok: kelas yang sama tidak
                // boleh punya 2 mapel/jadwal berbeda pada jam & hari yang
                // sama dalam periode yang sama. Ditolak eksplisit di sini
                // (bukan diam-diam menimpa lewat updateOrCreate seperti
                // sebelumnya).
                Rule::unique('jadwal_pelajarans', 'jam_pelajaran_id')
                    ->where(fn ($q) => $q->where('hari', $request->hari)
                        ->where('kelas_id', $request->kelas_id)
                        ->where('tahun_ajaran_id', $tahunAjaran->id)),
                // GURU bentrok: guru yang sama tidak boleh mengajar kelas
                // lain pada jam & hari yang sama dalam periode yang sama.
                Rule::unique('jadwal_pelajarans', 'jam_pelajaran_id')
                    ->where(fn ($q) => $q->where('hari', $request->hari)
                        ->where('guru_id', $request->guru_id)
                        ->where('tahun_ajaran_id', $tahunAjaran->id)
                        ->where('kelas_id', '!=', $request->kelas_id)),
            ],
        ], [
            'jam_pelajaran_id.exists' => 'Jam pelajaran yang dipilih tidak sesuai dengan hari yang dipilih.',
            'jam_pelajaran_id.unique' => 'Kelas ini sudah punya jadwal lain di jam yang sama pada hari tersebut, atau guru tersebut sudah dijadwalkan mengajar kelas lain di jam yang sama.',
            'guru_id.exists' => 'Guru tersebut tidak terdaftar mengajar mapel ini di kelas ini pada Mapping Guru Mengajar.',
        ]);
        $validated['tahun_ajaran_id'] = $tahunAjaran->id;

        // STEP 6 — dulu pakai updateOrCreate (bisa diam-diam menimpa jadwal
        // yang sudah ada di slot yang sama). Sekarang selalu create() murni
        // karena validasi di atas SUDAH menolak kalau slotnya sudah terisi
        // (kelas bentrok) — mengedit jadwal yang sudah ada dilakukan lewat
        // update(), bukan lewat form tambah ini.
        JadwalPelajaran::create($validated);

        return back()->with('success', 'Jadwal berhasil disimpan.');
    }

    public function update(Request $request, JadwalPelajaran $jadwal)
    {
        // STEP 2 Bagian 8: periode milik jadwal ini sendiri, bukan periode
        // aktif global.
        PeriodeAkademik::pastikanTidakTerkunci($jadwal->tahunAjaran);

        $validated = $request->validate([
            'hari' => ['required', Rule::in(JadwalPelajaran::HARI_LIST())],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
            'guru_id' => [
                'required',
                'exists:users,id',
                Rule::exists('guru_mengajar_kelas', 'guru_id')->where(fn ($q) => $q
                    ->where('kelas_id', $jadwal->kelas_id)
                    ->where('mata_pelajaran_id', $request->mata_pelajaran_id)
                    ->where('tahun_ajaran_id', $jadwal->tahun_ajaran_id)),
            ],
            'jam_pelajaran_id' => [
                'required',
                Rule::exists('jam_pelajarans', 'id')->where(fn ($q) => $q->where('hari', $request->hari)),
                Rule::unique('jadwal_pelajarans', 'jam_pelajaran_id')
                    ->where(fn ($q) => $q->where('hari', $request->hari)
                        ->where('kelas_id', $jadwal->kelas_id)
                        ->where('tahun_ajaran_id', $jadwal->tahun_ajaran_id))
                    ->ignore($jadwal->id),
                Rule::unique('jadwal_pelajarans', 'jam_pelajaran_id')
                    ->where(fn ($q) => $q->where('hari', $request->hari)
                        ->where('guru_id', $request->guru_id)
                        ->where('tahun_ajaran_id', $jadwal->tahun_ajaran_id)
                        ->where('kelas_id', '!=', $jadwal->kelas_id))
                    ->ignore($jadwal->id),
            ],
        ], [
            'jam_pelajaran_id.exists' => 'Jam pelajaran yang dipilih tidak sesuai dengan hari yang dipilih.',
            'jam_pelajaran_id.unique' => 'Kelas ini sudah punya jadwal lain di jam yang sama pada hari tersebut, atau guru tersebut sudah dijadwalkan mengajar kelas lain di jam yang sama.',
            'guru_id.exists' => 'Guru tersebut tidak terdaftar mengajar mapel ini di kelas ini pada Mapping Guru Mengajar.',
        ]);

        $jadwal->update($validated);

        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwal)
    {
        PeriodeAkademik::pastikanTidakTerkunci($jadwal->tahunAjaran);

        $jadwal->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function importForm()
    {
        return view('jadwal.import');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['hari', 'kode_kelas', 'jam_ke', 'kode_mapel', 'nip_guru'],
            [
                ['Senin', '7A', 1, 'MTK', '198501012010011001'],
                ['Senin', '7A', 2, 'IPA', '198502022011012002'],
            ],
            'Jadwal Pelajaran',
            [
                'Petunjuk:',
                '- hari diisi nama hari (Senin, Selasa, Rabu, Kamis, Jumat, atau Sabtu).',
                '- kode_kelas diisi sesuai nama kelas pada menu Data Kelas UNTUK TAHUN AJARAN AKTIF (contoh: 7A). Pastikan kelas tsb sudah dibuat untuk tahun ajaran yang sedang aktif sebelum import.',
                '- jam_ke diisi nomor jam pelajaran sesuai menu Jam Pelajaran untuk hari terkait.',
                '- kode_mapel diisi sesuai kode pada menu Mata Pelajaran (contoh: MTK).',
                '- nip_guru diisi dengan NIP guru yang sudah terdaftar di menu Kelola Pengguna.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-jadwal-pelajaran.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'mimes:xlsx,xls,csv']]);

        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        Excel::import(new JadwalImport($tahunAjaran->id), $request->file('file'));

        return redirect()->route('jadwal.index')->with('success', 'Import jadwal pelajaran berhasil.');
    }
}
