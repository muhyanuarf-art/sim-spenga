<?php

namespace App\Http\Controllers;

use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JamPelajaranController extends Controller
{
    public function index()
    {
        $jamPelajaranPerHari = JamPelajaran::orderBy('jam_ke')->get()->groupBy('hari');
        $hariList = JamPelajaran::HARI_LIST();

        return view('jam-pelajaran.index', compact('jamPelajaranPerHari', 'hariList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hari' => ['required', Rule::in(JamPelajaran::HARI_LIST())],
            'jam_ke' => [
                'required', 'integer', 'min:1', 'max:10',
                Rule::unique('jam_pelajarans', 'jam_ke')->where(fn ($q) => $q->where('hari', $request->hari)),
            ],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);

        JamPelajaran::create($validated);

        return back()->with('success', "Jam pelajaran hari {$validated['hari']} berhasil ditambahkan.");
    }

    public function update(Request $request, JamPelajaran $jamPelajaran)
    {
        $validated = $request->validate([
            'hari' => ['required', Rule::in(JamPelajaran::HARI_LIST())],
            'jam_ke' => [
                'required', 'integer', 'min:1', 'max:10',
                Rule::unique('jam_pelajarans', 'jam_ke')
                    ->where(fn ($q) => $q->where('hari', $request->hari))
                    ->ignore($jamPelajaran->id),
            ],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $jamPelajaran->update($validated);

        return back()->with('success', 'Jam pelajaran berhasil diperbarui. Perubahan ini otomatis berlaku di seluruh jadwal hari tersebut.');
    }

    public function destroy(JamPelajaran $jamPelajaran)
    {
        $jamPelajaran->delete();
        return back()->with('success', 'Jam pelajaran berhasil dihapus.');
    }

    public function salin(Request $request)
    {
        $validated = $request->validate([
            'hari_sumber' => ['required', Rule::in(JamPelajaran::HARI_LIST())],
            'hari_tujuan' => ['required', 'array', 'min:1'],
            'hari_tujuan.*' => ['required', Rule::in(JamPelajaran::HARI_LIST())],
        ], [
            'hari_tujuan.required' => 'Pilih minimal satu hari tujuan.',
        ]);

        $hariSumber = $validated['hari_sumber'];
        $hariTujuan = array_values(array_unique(array_diff($validated['hari_tujuan'], [$hariSumber])));

        if (empty($hariTujuan)) {
            return back()->with('error', 'Hari tujuan tidak boleh sama dengan hari sumber.');
        }

        $sumberList = JamPelajaran::where('hari', $hariSumber)->orderBy('jam_ke')->get();

        if ($sumberList->isEmpty()) {
            return back()->with('error', "Belum ada jam pelajaran di hari {$hariSumber} untuk disalin.");
        }

        DB::transaction(function () use ($sumberList, $hariTujuan) {
            $jamKeSumber = $sumberList->pluck('jam_ke')->all();

            foreach ($hariTujuan as $hari) {
                // Update-in-place untuk jam ke yang sama persis (ID baris dipertahankan),
                // supaya Jadwal Pelajaran/Jurnal Mengajar/Absensi yang sudah terekam di jam
                // tersebut TIDAK ikut terhapus. Hanya slot jam ke yang tidak ada di sumber
                // (kelebihan) yang akan dihapus dari hari tujuan.
                JamPelajaran::where('hari', $hari)
                    ->whereNotIn('jam_ke', $jamKeSumber)
                    ->delete();

                foreach ($sumberList as $jam) {
                    JamPelajaran::updateOrCreate(
                        ['hari' => $hari, 'jam_ke' => $jam->jam_ke],
                        [
                            'jam_mulai' => $jam->jam_mulai,
                            'jam_selesai' => $jam->jam_selesai,
                            'is_active' => $jam->is_active,
                        ]
                    );
                }
            }
        });

        return back()->with('success', "Jam pelajaran hari {$hariSumber} berhasil disalin ke " . implode(', ', $hariTujuan) . '.');
    }
}
