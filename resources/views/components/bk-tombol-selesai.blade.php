{{--
    TOMBOL STATUS SATU KLIK — dipakai di SEMUA tempat status BK bisa diubah
    (daftar Kasus, daftar Pembinaan, Ringkasan BK, dan Profil Perilaku Siswa).

    Dulu status hanya bisa diubah lewat dropdown kecil di dalam tabel Riwayat
    pada halaman Profil Perilaku Siswa — di daftar Kasus & Pembinaan statusnya
    cuma badge yang tidak bisa diklik. Akibatnya menandai satu pembinaan
    "Selesai" perlu berpindah halaman, mencari siswanya, lalu mencari barisnya.

    Sekarang cukup satu klik dari mana pun barisnya terlihat. Dibuat sebagai
    komponen supaya bentuk, warna, dan perilakunya persis sama di keempat
    tempat itu — kalau nanti berubah, cukup diubah di berkas ini.

    Props:
    - action        : URL tujuan (rute update status)
    - metode        : PATCH untuk kasus, PUT untuk pembinaan
    - selesai       : keadaan sekarang (bool)
    - statusSelesai : nilai yang dikirim untuk menandai selesai
    - statusBuka    : nilai yang dikirim saat dibuka kembali
    - konfirmasiBuka: tanya dulu sebelum membuka kembali (default true)

    Slot dipakai untuk input tersembunyi tambahan (mis. hasil_pembinaan yang
    harus ikut terkirim agar tidak terhapus).
--}}
@props([
    'action',
    'metode' => 'PATCH',
    'selesai' => false,
    'statusSelesai' => 'Selesai',
    'statusBuka' => 'Baru',
    'konfirmasiBuka' => true,
])

<form method="POST" action="{{ $action }}" class="inline-block"
      @if($selesai && $konfirmasiBuka) data-konfirmasi="Buka kembali catatan ini? Statusnya akan kembali menjadi Belum Selesai." @endif>
    @csrf
    @method($metode)
    <input type="hidden" name="status" value="{{ $selesai ? $statusBuka : $statusSelesai }}">
    {{ $slot }}

    <button type="submit"
            class="btn-chip {{ $selesai ? 'btn-chip-cancel' : 'btn-chip-success' }}"
            title="{{ $selesai ? 'Kembalikan ke Belum Selesai' : 'Tandai catatan ini sudah selesai' }}">
        <i class="fa-solid {{ $selesai ? 'fa-rotate-left' : 'fa-check' }}"></i>
        {{ $selesai ? 'Buka Kembali' : 'Tandai Selesai' }}
    </button>
</form>
