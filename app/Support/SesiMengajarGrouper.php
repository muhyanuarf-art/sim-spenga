<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SesiMengajarGrouper
{
    /**
     * Kelompokkan baris JadwalPelajaran (1 baris = 1 jam pelajaran) menjadi
     * beberapa "sesi mengajar". Baris digabung ke sesi yang sama jika:
     *  - kelas_id sama
     *  - mata_pelajaran_id sama
     *  - jam_ke-nya berurutan langsung (tidak ada jam kosong di antaranya)
     *
     * @param  Collection<int, \App\Models\JadwalPelajaran>  $jadwal  Harus sudah eager-load relasi jamPelajaran, dan diurutkan menaik berdasarkan jam_ke.
     * @return Collection<int, array{
     *     slots: Collection<int, \App\Models\JadwalPelajaran>,
     *     ids: string,
     *     kelas: mixed,
     *     mapel: mixed,
     *     jam_awal: mixed,
     *     jam_akhir: mixed,
     * }>
     */
    public static function kelompokkan(Collection $jadwal): Collection
    {
        $sorted = $jadwal->sortBy(fn ($j) => $j->jamPelajaran->jam_ke)->values();

        $groups = collect();
        $bufer = collect();

        foreach ($sorted as $j) {
            if ($bufer->isEmpty()) {
                $bufer->push($j);
                continue;
            }

            $terakhir = $bufer->last();
            $berurutan = $j->jamPelajaran->jam_ke === $terakhir->jamPelajaran->jam_ke + 1;
            $samaKelasMapel = $j->kelas_id === $terakhir->kelas_id
                && $j->mata_pelajaran_id === $terakhir->mata_pelajaran_id;

            if ($berurutan && $samaKelasMapel) {
                $bufer->push($j);
            } else {
                $groups->push(self::buatSesi($bufer));
                $bufer = collect([$j]);
            }
        }

        if ($bufer->isNotEmpty()) {
            $groups->push(self::buatSesi($bufer));
        }

        return $groups;
    }

    private static function buatSesi(Collection $slots): array
    {
        $pertama = $slots->first();
        $terakhir = $slots->last();

        return [
            'slots' => $slots,
            'ids' => $slots->pluck('id')->implode(','),
            'kelas' => $pertama->kelas,
            'mapel' => $pertama->mapel,
            'guru_id' => $pertama->guru_id,
            'jam_awal' => $pertama->jamPelajaran,
            'jam_akhir' => $terakhir->jamPelajaran,
        ];
    }
}
