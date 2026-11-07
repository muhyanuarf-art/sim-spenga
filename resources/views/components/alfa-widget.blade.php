{{--
    SISWA ALFA HARI INI.

    Props, data, dan isi tabelnya sama persis seperti sebelumnya — yang
    berubah hanya bungkusnya.

    Dulu kepala kartu ini berupa spanduk gradasi merah-ke-oranye setinggi
    64px. Di dashboard yang seluruh kartunya putih bergaris rambut, satu
    spanduk menyala membuat panel ini berteriak paling keras — padahal
    pada hari yang baik isinya justru "tidak ada siswa Alfa". Perhatian
    terbesar diberikan kepada kartu yang paling sering kosong.

    Sekarang bentuknya sama dengan kartu lain, dan yang menandai keadaan
    adalah ANGKANYA: nol tampil hijau tenang, selebihnya merah. Jadi
    warnanya mengikuti keadaan hari itu, bukan menyala terus-menerus.
--}}
@props(['data', 'title' => 'Siswa Alfa Hari Ini', 'subtitle' => null, 'showKelas' => true])

@php
    $jumlahAlfa = $data->count();
@endphp

<div class="card">
    <div class="card-head">
        <div class="min-w-0 flex items-start gap-3">
            <span class="aksen-batang" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="section-title tanpa-aksen">{{ $title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $subtitle ?? 'Absensi Kelas — status dari guru mapel dengan jam paling akhir hari ini' }}
                </p>
            </div>
        </div>
        <span class="badge shrink-0 {{ $jumlahAlfa > 0 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
            {{ $jumlahAlfa }} siswa
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="table-clean w-full">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    @if($showKelas)<th>Kelas</th>@endif
                    <th>Menurut Mapel</th>
                    <th>Jam</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $a)
                <tr>
                    <td class="font-medium">
                        <div class="flex items-center gap-2">
                            <x-initial-avatar :nama="$a['siswa']->nama ?? '-'" />
                            {{ $a['siswa']->nama ?? '-' }}
                        </div>
                    </td>
                    @if($showKelas)
                        <td><x-kelas-badge :nama="$a['kelas']->nama_kelas ?? '-'" /></td>
                    @endif
                    <td><x-mapel-badge :nama="$a['mapel'] ?? '-'" /></td>
                    <td class="text-slate-500 font-medium">{{ $a['jam_ke'] ? "Jam ke-{$a['jam_ke']}" : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $showKelas ? 4 : 3 }}" class="text-center text-emerald-600 py-8">
                        <i class="fa-solid fa-circle-check mr-1.5"></i> Tidak ada siswa Alfa hari ini — kehadiran lancar!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
