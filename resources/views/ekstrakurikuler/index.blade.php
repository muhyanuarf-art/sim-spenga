@extends('layouts.app')
@section('title', 'Kegiatan Ekstrakurikuler')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <p class="text-sm text-slate-500">Daftar kegiatan ekstrakurikuler sekolah. Anggota &amp; absensi per kegiatan dikelola di menu terpisah setelah kegiatannya terdaftar di sini.</p>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Kegiatan</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Kegiatan Ekstrakurikuler</p>
        <form method="POST" action="{{ route('ekstrakurikuler.store') }}" class="space-y-4" x-data="eksternalPembinaForm([])">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <input type="text" name="nama_ekstrakurikuler" placeholder="Nama Kegiatan, contoh: Pramuka" required class="input">
                <input type="text" name="keterangan" placeholder="Keterangan (opsional)" class="input">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Pembina dari Sekolah (boleh pilih lebih dari satu)</label>
                    <div class="border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto space-y-1.5">
                        @forelse($calonPembina as $u)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="pembina_internal[]" value="{{ $u->id }}">
                                <span>{{ $u->name }} <span class="text-slate-400 text-xs">({{ $u->nip ?: $u->email }})</span></span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data guru/staf.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Pembina dari Luar Sekolah (opsional)</label>

                    <ul class="space-y-1.5 mb-2" x-show="items.length > 0">
                        <template x-for="(item, i) in items" :key="i">
                            <li class="flex items-center justify-between gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-sm">
                                <span>
                                    <span x-text="item.nama"></span>
                                    <span class="text-slate-400" x-show="item.kontak" x-text="' — ' + item.kontak"></span>
                                </span>
                                <button type="button" @click="hapus(i)" class="text-slate-400 hover:text-red-500">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </li>
                        </template>
                    </ul>

                    <template x-if="!showAddForm">
                        <button type="button" @click="showAddForm = true" class="btn-outline text-xs px-3 py-1.5">
                            <i class="fa-solid fa-plus mr-1"></i> Tambah Pembina Luar Sekolah
                        </button>
                    </template>

                    <div x-show="showAddForm" x-cloak class="border border-slate-200 rounded-lg p-3 space-y-2 bg-slate-50/60">
                        <input type="text" x-model="formNama" placeholder="Nama pembina" class="input">
                        <input type="text" x-model="formKontak" placeholder="Kontak / No HP (opsional)" class="input">
                        <div class="flex gap-2">
                            <button type="button" @click="tambah()" class="btn-primary h-[34px] text-xs px-3">Input</button>
                            <button type="button" @click="batal()" class="btn-outline h-[34px] text-xs px-3">Batal</button>
                        </div>
                    </div>

                    <template x-for="(item, i) in items" :key="'hidden-'+i">
                        <span>
                            <input type="hidden" name="pembina_eksternal_nama[]" :value="item.nama">
                            <input type="hidden" name="pembina_eksternal_kontak[]" :value="item.kontak">
                        </span>
                    </template>
                </div>
            </div>

            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($ekstrakurikuler as $e)
        <div class="card p-5" x-data="{ editing: false }">
            <div x-show="!editing">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-slate-800 text-base">{{ $e->nama_ekstrakurikuler }}</p>
                            @if($e->is_aktif)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-400">Nonaktif</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-600 mt-1">Pembina: {{ $e->daftarNamaPembina() }}</p>
                        @if($e->keterangan)
                            <p class="text-sm text-slate-400 mt-0.5">{{ $e->keterangan }}</p>
                        @endif
                    </div>

                    {{-- Kelola kegiatan ini sendiri (ganti nama/pembina, hapus) —
                         dipisah dari tombol Anggota/Absensi/Rekap di bawah karena
                         tujuannya beda: ini mengubah DATA kegiatannya, bukan
                         membuka laporan/menu terkait. Tombol langsung (bukan
                         menu dropdown) supaya aksinya kelihatan & bisa langsung
                         disentuh tanpa buka menu dulu. --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" @click="editing = true"
                                class="w-10 h-10 flex items-center justify-center rounded-lg text-brand-600 cursor-pointer hover:bg-brand-50 active:bg-brand-100 active:text-brand-700 transition"
                                title="Edit Kegiatan" aria-label="Edit Kegiatan {{ $e->nama_ekstrakurikuler }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ route('ekstrakurikuler.destroy', $e) }}" onsubmit="return confirm('Hapus kegiatan ekstrakurikuler ini?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-10 h-10 flex items-center justify-center rounded-lg text-red-500 cursor-pointer hover:bg-red-50 active:bg-red-100 active:text-red-700 transition"
                                    title="Hapus Kegiatan" aria-label="Hapus Kegiatan {{ $e->nama_ekstrakurikuler }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Jalan pintas ke halaman terkait kegiatan ini (bukan mengubah
                     data kegiatannya) — dibuat sebagai ubin besar supaya nyaman
                     disentuh lewat HP, bukan tombol kecil berjejer. --}}
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mt-4 mb-2">Anggota &amp; Laporan</p>
                <div class="grid grid-cols-3 gap-3">
                    <a href="{{ route('ekstrakurikuler.anggota.index', $e) }}"
                       class="flex flex-col items-center justify-center gap-2 px-2 py-4 rounded-xl border border-slate-200 text-slate-700 cursor-pointer hover:border-brand-300 hover:bg-brand-50/60 active:border-brand-400 active:bg-brand-100 transition">
                        <i class="fa-solid fa-users text-xl text-brand-600"></i>
                        <span class="text-xs font-semibold text-center leading-tight">Anggota</span>
                    </a>
                    <a href="{{ route('ekstrakurikuler.absensi.form', $e) }}"
                       class="flex flex-col items-center justify-center gap-2 px-2 py-4 rounded-xl border border-slate-200 text-slate-700 cursor-pointer hover:border-brand-300 hover:bg-brand-50/60 active:border-brand-400 active:bg-brand-100 transition">
                        <i class="fa-solid fa-clipboard-check text-xl text-brand-600"></i>
                        <span class="text-xs font-semibold text-center leading-tight">Absensi</span>
                    </a>
                    <a href="{{ route('ekstrakurikuler.rekap', $e) }}"
                       class="flex flex-col items-center justify-center gap-2 px-2 py-4 rounded-xl border border-slate-200 text-slate-700 cursor-pointer hover:border-brand-300 hover:bg-brand-50/60 active:border-brand-400 active:bg-brand-100 transition">
                        <i class="fa-solid fa-chart-column text-xl text-brand-600"></i>
                        <span class="text-xs font-semibold text-center leading-tight">Rekap</span>
                    </a>
                </div>
            </div>

            <div x-show="editing" x-cloak class="bg-brand-50/40 -m-5 p-5 rounded-lg">
                @php
                    $idInternalTerpilih = $e->pembinas->whereNull('nama_eksternal')->pluck('user_id')->all();
                    $eksternalAwal = $e->pembinas->whereNotNull('nama_eksternal')
                        ->map(fn($p) => ['nama' => $p->nama_eksternal, 'kontak' => $p->kontak_eksternal])
                        ->values();
                @endphp
                <form method="POST" action="{{ route('ekstrakurikuler.update', $e) }}" class="space-y-4" x-data="eksternalPembinaForm({{ \Illuminate\Support\Js::from($eksternalAwal) }})">
                    @csrf @method('PUT')
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="text" name="nama_ekstrakurikuler" value="{{ $e->nama_ekstrakurikuler }}" required class="input">
                        <input type="text" name="keterangan" value="{{ $e->keterangan }}" placeholder="Keterangan" class="input">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Pembina dari Sekolah</label>
                            <div class="border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto space-y-1.5 bg-white">
                                @foreach($calonPembina as $u)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="pembina_internal[]" value="{{ $u->id }}" @checked(in_array($u->id, $idInternalTerpilih))>
                                        <span>{{ $u->name }} <span class="text-slate-400 text-xs">({{ $u->nip ?: $u->email }})</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Pembina dari Luar Sekolah</label>

                            <ul class="space-y-1.5 mb-2" x-show="items.length > 0">
                                <template x-for="(item, i) in items" :key="i">
                                    <li class="flex items-center justify-between gap-2 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-sm">
                                        <span>
                                            <span x-text="item.nama"></span>
                                            <span class="text-slate-400" x-show="item.kontak" x-text="' — ' + item.kontak"></span>
                                        </span>
                                        <button type="button" @click="hapus(i)" class="text-slate-400 hover:text-red-500">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </li>
                                </template>
                            </ul>

                            <template x-if="!showAddForm">
                                <button type="button" @click="showAddForm = true" class="btn-outline text-xs px-3 py-1.5">
                                    <i class="fa-solid fa-plus mr-1"></i> Tambah Pembina Luar Sekolah
                                </button>
                            </template>

                            <div x-show="showAddForm" x-cloak class="border border-slate-200 rounded-lg p-3 space-y-2 bg-white">
                                <input type="text" x-model="formNama" placeholder="Nama pembina" class="input">
                                <input type="text" x-model="formKontak" placeholder="Kontak / No HP (opsional)" class="input">
                                <div class="flex gap-2">
                                    <button type="button" @click="tambah()" class="btn-primary h-[34px] text-xs px-3">Input</button>
                                    <button type="button" @click="batal()" class="btn-outline h-[34px] text-xs px-3">Batal</button>
                                </div>
                            </div>

                            <template x-for="(item, i) in items" :key="'hidden-'+i">
                                <span>
                                    <input type="hidden" name="pembina_eksternal_nama[]" :value="item.nama">
                                    <input type="hidden" name="pembina_eksternal_kontak[]" :value="item.kontak">
                                </span>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 flex-wrap">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_aktif" value="1" @checked($e->is_aktif)> Aktif
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                            <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card p-5 text-center text-slate-400 py-8">Belum ada data.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $ekstrakurikuler->links() }}</div>
</div>

<script>
    // Data pembina LUAR SEKOLAH untuk 1 form (tambah baru atau edit 1
    // kegiatan) — dipakai lewat x-data="eksternalPembinaForm(dataAwal)".
    // `items` yang jadi sumber kebenaran, dikirim ke server lewat pasangan
    // <input type="hidden"> (nama_eksternal[]/kontak_eksternal[]) yang
    // di-render ulang otomatis oleh Alpine tiap `items` berubah.
    function eksternalPembinaForm(dataAwal) {
        return {
            items: dataAwal || [],
            showAddForm: false,
            formNama: '',
            formKontak: '',
            tambah() {
                if (!this.formNama.trim()) return;
                this.items.push({ nama: this.formNama.trim(), kontak: this.formKontak.trim() });
                this.formNama = '';
                this.formKontak = '';
                this.showAddForm = false;
            },
            batal() {
                this.formNama = '';
                this.formKontak = '';
                this.showAddForm = false;
            },
            hapus(i) {
                this.items.splice(i, 1);
            },
        }
    }
</script>
@endsection
