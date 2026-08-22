@props(['data', 'title' => 'Siswa Alfa Hari Ini', 'subtitle' => null, 'showKelas' => true])

<div class="relative overflow-hidden rounded-2xl border border-rose-100 shadow-soft">
    <div class="bg-gradient-to-r from-rose-500 via-rose-500 to-orange-400 px-5 py-4 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-bold text-white flex items-center gap-2">
                <span class="text-lg"><i class="fa-solid fa-flag"></i></span> {{ $title }}
            </p>
            <p class="text-xs text-rose-50/90 mt-0.5">
                {{ $subtitle ?? 'Absensi Kelas — status dari guru mapel dengan jam paling akhir hari ini' }}
            </p>
        </div>
        <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-lg font-extrabold text-white shrink-0">
            {{ $data->count() }}
        </div>
    </div>
    <div class="bg-white overflow-x-auto">
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
