@extends('layouts.app')
@section('title', 'Pemetaan Guru BK')

@section('content')
{{-- Form dibuka kembali bila isiannya ditolak, supaya centangan kelas
     yang sudah dipilih tidak tampak hilang. Sama seperti Pemetaan Guru
     Mengajar. --}}
<div class="space-y-6" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Aktifkan Tahun Ajaran terlebih dahulu sebelum menambah mapping.
        </div>
    @endif

    @if($guruBkList->isEmpty())
        <div class="rounded-xl bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 text-sm">
            <i class="fa-solid fa-circle-info mr-1.5"></i> Belum ada pengguna dengan role <b>Guru BK</b>. Tambahkan dulu di menu
            <a href="{{ route('users.index') }}" class="underline font-semibold">Kelola Pengguna</a>
            (pilih role "Guru BK"), baru bisa di-mapping ke kelas di sini.
        </div>
    @endif

    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-slate-400">Tentukan kelas mana saja yang dipantau tiap Guru BK.</p>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapping</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mapping Guru BK</p>
        {{-- Kelas dicentang, tidak lagi dipilih satu per satu. Gayanya
             sengaja SAMA PERSIS dengan Pemetaan Guru Mengajar supaya
             operator tidak perlu belajar dua cara untuk pekerjaan yang
             sama. Yang tersimpan tetap satu baris per guru + kelas. --}}
        <form method="POST" action="{{ route('kurikulum.guru-bk.store') }}"
              class="space-y-4"
              x-data="{ terpilih: @js(array_map('intval', (array) old('kelas_id', []))) }">
            @csrf

            <div class="sm:max-w-md">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru BK</label>
                <select name="guru_id" required class="input">
                    <option value="">Pilih Guru BK</option>
                    @foreach($guruBkList as $g)
                        <option value="{{ $g->id }}" @selected(old('guru_id') == $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex items-baseline justify-between flex-wrap gap-2 mb-2">
                    <label class="block text-xs font-semibold text-slate-500">
                        Kelas binaan
                        <span class="font-normal text-slate-400">— centang boleh lebih dari satu</span>
                    </label>
                    <p class="text-xs font-semibold text-emerald-600" x-show="terpilih.length > 0" x-cloak>
                        <span x-text="terpilih.length"></span> kelas dipilih
                    </p>
                </div>

                @forelse($kelasList->groupBy('tingkat') as $tingkat => $kelasTingkat)
                    @php $idTingkat = $kelasTingkat->pluck('id')->map(fn ($i) => (int) $i)->values(); @endphp
                    <div class="flex items-center gap-3 py-2 {{ ! $loop->first ? 'border-t border-slate-100' : '' }}">
                        <span class="shrink-0 w-[72px] text-xs font-bold text-slate-500">Kelas {{ $tingkat }}</span>

                        <div class="flex-1 flex items-center gap-2 flex-wrap">
                            @foreach($kelasTingkat as $k)
                                <label class="cursor-pointer select-none">
                                    <input type="checkbox" name="kelas_id[]" value="{{ $k->id }}"
                                           x-model.number="terpilih" class="sr-only">
                                    <span class="inline-flex items-center justify-center gap-1.5 min-w-[56px] h-9 px-3 rounded-lg border text-sm font-semibold transition"
                                          :class="terpilih.includes({{ $k->id }})
                                              ? 'bg-emerald-600 border-emerald-600 text-white shadow-sm'
                                              : 'bg-white border-slate-200 text-slate-600 hover:border-emerald-300 hover:text-emerald-700'">
                                        <i class="fa-solid fa-check text-[11px]"
                                           x-show="terpilih.includes({{ $k->id }})" x-cloak></i>
                                        {{ $k->nama_kelas }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <button type="button"
                                class="shrink-0 h-9 px-3 rounded-lg border text-xs font-bold transition"
                                :class="@js($idTingkat).every(i => terpilih.includes(i))
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                    : 'border-slate-200 bg-white text-slate-500 hover:border-emerald-300 hover:text-emerald-700'"
                                @click="terpilih = @js($idTingkat).every(i => terpilih.includes(i))
                                        ? terpilih.filter(i => ! @js($idTingkat).includes(i))
                                        : [...new Set([...terpilih, ...@js($idTingkat)])]"
                                x-text="@js($idTingkat).every(i => terpilih.includes(i)) ? 'Batalkan semua' : 'Pilih semua'">
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-2">Belum ada kelas pada tahun ajaran aktif. Tambahkan dulu di menu Data Kelas.</p>
                @endforelse
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary h-[38px]" x-bind:disabled="terpilih.length === 0">Simpan</button>
                <button type="button" class="btn-ghost h-[38px]" x-show="terpilih.length > 0" x-cloak
                        @click="terpilih = []">Bersihkan pilihan</button>
            </div>
        </form>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="guru_id" class="input max-w-[220px]" onchange="this.form.submit()">
                <option value="">Semua Guru BK</option>
                @foreach($guruBkList as $g)<option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
            </select>
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-12 text-center">No</th><th>Guru BK</th><th>Kelas</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $d)
                    <tr>
                        <td class="text-center text-slate-400">{{ $data->firstItem() + $loop->index }}</td>
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$d->guru->name ?? '-'" />
                                {{ $d->guru->name ?? '-' }}
                            </div>
                        </td>
                        <td><x-kelas-badge :nama="$d->kelas->nama_kelas ?? '-'" /></td>
                        <td class="td-aksi">
                            <form method="POST" action="{{ route('kurikulum.guru-bk.destroy', $d) }}" data-konfirmasi="Hapus mapping ini?">
                                @csrf @method('DELETE')
                                <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-8">Belum ada mapping Guru BK.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
