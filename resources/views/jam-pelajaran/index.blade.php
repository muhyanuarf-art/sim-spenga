@extends('layouts.app')
@section('title', 'Jam Pelajaran')

@section('content')
<div class="space-y-6" x-data="{ hariAktif: '{{ old('hari', request('hari', $hariList[0])) }}', showForm: false, showSalin: false }">
    <div class="rounded-xl bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-info mr-1.5"></i> Jam pelajaran sekarang diatur <b>per hari</b> (Senin - Sabtu), karena waktu setiap hari bisa berbeda-beda.
        Setiap perubahan otomatis terhubung ke Jadwal Pelajaran, Absensi, dan tampilan Guru/Wali Kelas pada hari yang bersangkutan.
    </div>

    {{-- Tab hari --}}
    <div class="card p-2 flex flex-wrap gap-1">
        @foreach($hariList as $h)
            <button type="button" @click="hariAktif = '{{ $h }}'"
                    :class="hariAktif === '{{ $h }}' ? 'bg-brand-600 text-white shadow-soft' : 'text-slate-500 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl text-sm font-bold transition">
                {{ $h }}
                <span :class="hariAktif === '{{ $h }}' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-400'"
                      class="ml-1.5 px-1.5 py-0.5 rounded-full text-[11px]">{{ ($jamPelajaranPerHari[$h] ?? collect())->count() }}</span>
            </button>
        @endforeach
    </div>

    @if(auth()->user()->role === 'admin')
    <div class="flex justify-end gap-2">
        <button @click="showSalin = !showSalin" class="btn-outline"><i class="fa-solid fa-arrows-rotate mr-1.5"></i> Salin ke Hari Lain</button>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Jam Ke</button>
    </div>

    <div class="card p-5" x-show="showSalin" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-1">Salin Jam Pelajaran ke Hari Lain</p>
        <p class="text-sm text-slate-400 mb-4">
            Semua jam pelajaran dari hari sumber akan disalin (jam ke, jam mulai, jam selesai, status aktif) ke hari
            tujuan yang dipilih. Jam pelajaran yang jam ke-nya sudah ada di hari tujuan akan diperbarui datanya
            (jadwal/absensi yang sudah terekam pada jam tersebut tidak ikut terhapus); jam ke lain yang berlebih di
            hari tujuan akan dihapus.
        </p>
        <form method="POST" action="{{ route('jam-pelajaran.salin') }}" class="space-y-4">
            @csrf
            @php($hariDefault = old('hari', request('hari', $hariList[0])))
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Salin dari hari</label>
                <select name="hari_sumber" required class="input max-w-xs">
                    @foreach($hariList as $h)
                        <option value="{{ $h }}" {{ $hariDefault === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Salin ke hari (bisa pilih lebih dari satu)</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($hariList as $h)
                        <label class="flex items-center gap-1.5 text-sm text-slate-600 font-semibold">
                            <input type="checkbox" name="hari_tujuan[]" value="{{ $h }}" class="rounded">
                            {{ $h }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary" onclick="return confirm('Yakin salin jam pelajaran? Data jam pelajaran di hari tujuan yang jam ke-nya tidak ada di hari sumber akan dihapus.')">Salin Sekarang</button>
                <button type="button" @click="showSalin = false" class="btn-outline">Batal</button>
            </div>
        </form>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Jam Pelajaran</p>
        <form method="POST" action="{{ route('jam-pelajaran.store') }}" class="grid sm:grid-cols-5 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hari</label>
                <select name="hari" x-model="hariAktif" required class="input">
                    @foreach($hariList as $h)<option value="{{ $h }}">{{ $h }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jam Ke</label>
                <select name="jam_ke" required class="input">
                    @for($i = 1; $i <= 10; $i++)<option value="{{ $i }}">Jam Ke-{{ $i }}</option>@endfor
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mulai</label>
                <input type="time" name="jam_mulai" required class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Selesai</label>
                <input type="time" name="jam_selesai" required class="input">
            </div>
            <button type="submit" class="btn-primary h-[42px]">Simpan</button>
        </form>
    </div>
    @endif

    @foreach($hariList as $h)
    <div class="card p-5" x-show="hariAktif === '{{ $h }}'" x-cloak>
        <p class="font-bold text-slate-800 mb-4 text-lg">Jam Pelajaran - {{ $h }}</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Jam Ke</th><th>Mulai</th><th>Selesai</th><th>Status</th>@if(auth()->user()->role === 'admin')<th class="th-aksi">Aksi</th>@endif</tr></thead>
                @forelse(($jamPelajaranPerHari[$h] ?? collect()) as $j)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">Ke-{{ $j->jam_ke }}</td>
                        <td>{{ substr($j->jam_mulai, 0, 5) }}</td>
                        <td>{{ substr($j->jam_selesai, 0, 5) }}</td>
                        <td>
                            @if($j->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        @if(auth()->user()->role === 'admin')
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('jam-pelajaran.destroy', $j) }}" onsubmit="return confirm('Hapus jam pelajaran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @if(auth()->user()->role === 'admin')
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('jam-pelajaran.update', $j) }}" class="grid sm:grid-cols-6 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <select name="hari" required class="input">
                                    @foreach($hariList as $hh)
                                        <option value="{{ $hh }}" {{ $j->hari === $hh ? 'selected' : '' }}>{{ $hh }}</option>
                                    @endforeach
                                </select>
                                <select name="jam_ke" required class="input">
                                    @for($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}" {{ $j->jam_ke == $i ? 'selected' : '' }}>Jam Ke-{{ $i }}</option>
                                    @endfor
                                </select>
                                <input type="time" name="jam_mulai" value="{{ substr($j->jam_mulai, 0, 5) }}" required class="input">
                                <input type="time" name="jam_selesai" value="{{ substr($j->jam_selesai, 0, 5) }}" required class="input">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $j->is_active ? 'checked' : '' }} class="rounded">
                                    Aktif
                                </label>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="{{ auth()->user()->role === 'admin' ? 5 : 4 }}" class="text-center text-slate-400 py-8">Belum ada jam pelajaran untuk hari {{ $h }}.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
