{{--
    LAPORAN HASIL IMPORT EXCEL — dipakai di kelima halaman import.

    Sebelumnya, baris Excel yang bermasalah dilewati DIAM-DIAM sementara
    pengguna tetap diberi pesan hijau "Import berhasil". Operator sekolah
    tidak punya cara tahu bahwa, misalnya, 40 dari 300 siswa tidak jadi
    masuk karena kode_kelas-nya salah ketik.

    Komponen ini menampilkan hasilnya apa adanya: berapa baris baru, berapa
    diperbarui, dan SETIAP baris yang dilewati lengkap dengan nomor barisnya
    di file Excel serta alasannya — supaya operator tinggal membuka file,
    memperbaiki baris yang disebut, lalu mengunggah ulang.

    Datanya dari session 'hasil_import' yang diisi App\Support\JalankanImport.
--}}
@php $hasil = session('hasil_import'); @endphp

@if($hasil)
    <div class="card p-5">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
            <div>
                <p class="section-title">Hasil Import Terakhir</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $hasil['ringkasan'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="rounded-xl bg-emerald-50 px-4 py-3">
                <p class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Baris Baru</p>
                <p class="text-2xl font-extrabold text-emerald-700">{{ $hasil['dibuat'] }}</p>
            </div>
            <div class="rounded-xl bg-sky-50 px-4 py-3">
                <p class="text-[11px] font-semibold text-sky-700 uppercase tracking-wide">Diperbarui</p>
                <p class="text-2xl font-extrabold text-sky-700">{{ $hasil['diperbarui'] }}</p>
            </div>
            <div class="rounded-xl px-4 py-3 {{ count($hasil['dilewati']) > 0 ? 'bg-rose-50' : 'bg-slate-50' }}">
                <p class="text-[11px] font-semibold uppercase tracking-wide {{ count($hasil['dilewati']) > 0 ? 'text-rose-700' : 'text-slate-500' }}">Dilewati</p>
                <p class="text-2xl font-extrabold {{ count($hasil['dilewati']) > 0 ? 'text-rose-700' : 'text-slate-400' }}">{{ count($hasil['dilewati']) }}</p>
            </div>
        </div>

        @if(count($hasil['dilewati']) > 0)
            <p class="text-xs text-slate-500 mb-2">
                Baris di bawah ini <b>tidak tersimpan</b>. Perbaiki di file Excel Anda sesuai nomor barisnya,
                lalu unggah ulang — baris yang sudah masuk tidak akan tergandakan.
            </p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th class="w-24 text-center">Baris Excel</th>
                            <th class="w-40">Data</th>
                            <th>Alasan Dilewati</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil['dilewati'] as $d)
                            <tr>
                                <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                                <td class="text-center">
                                    <span class="badge bg-slate-100 text-slate-600">Baris {{ $d['baris'] }}</span>
                                </td>
                                <td class="font-medium text-slate-700">{{ $d['penanda'] ?: '—' }}</td>
                                <td class="text-slate-600">{{ $d['alasan'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-xs text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">
                <i class="fa-solid fa-circle-check mr-1.5"></i>
                Seluruh baris pada file berhasil diproses — tidak ada yang dilewati.
            </p>
        @endif
    </div>
@endif
