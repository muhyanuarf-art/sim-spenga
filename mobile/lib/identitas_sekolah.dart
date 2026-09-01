import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'layanan.dart';

/// Nama, inisial, dan logo sekolah untuk layar masuk.
///
/// =====================================================================
/// DARI MANA DATANYA
/// =====================================================================
/// Dari /site.webmanifest — endpoint yang SUDAH ada dan SUDAH terbuka
/// tanpa login (lihat App\Http\Controllers\IkonAplikasiController).
/// Peramban memintanya sendiri di halaman login, jadi tidak ada endpoint
/// baru yang perlu dibuat hanya demi aplikasi ini.
///
/// Isinya mengikuti Logo Aplikasi & Nama Sekolah di menu Pengaturan
/// Sekolah. Sekolah lain yang memakai aplikasi ini otomatis melihat
/// identitasnya sendiri di layar masuk, tanpa APK yang dibangun ulang.
///
/// =====================================================================
/// KENAPA DISIMPAN
/// =====================================================================
/// Layar masuk tampil sebelum ada jaringan yang terbukti jalan. Yang
/// terakhir diketahui disimpan supaya logo & nama sekolah langsung
/// tampil saat aplikasi dibuka, lalu diperbarui diam-diam begitu server
/// menjawab. Kalau server tidak terjangkau, yang lama tetap dipakai.
class IdentitasSekolah {
  final String nama;
  final String inisial;
  final String? logoUrl;

  const IdentitasSekolah({
    required this.nama,
    required this.inisial,
    this.logoUrl,
  });

  static const _kunciNama = 'sekolah_nama';
  static const _kunciInisial = 'sekolah_inisial';
  static const _kunciLogo = 'sekolah_logo';

  /// Yang terakhir berhasil diambil, atau null bila belum pernah.
  static Future<IdentitasSekolah?> tersimpan() async {
    final p = await SharedPreferences.getInstance();
    final nama = p.getString(_kunciNama);
    if (nama == null || nama.isEmpty) return null;

    return IdentitasSekolah(
      nama: nama,
      inisial: p.getString(_kunciInisial) ?? 'SIM',
      logoUrl: p.getString(_kunciLogo),
    );
  }

  /// Ambil dari server. null bila gagal — pemanggil tetap memakai yang
  /// tersimpan, jadi kegagalan di sini tidak boleh mengganggu apa pun.
  static Future<IdentitasSekolah?> ambilDariServer() async {
    final alamat = await Layanan.alamatServer();

    try {
      final res = await http
          .get(
            Uri.parse('$alamat/site.webmanifest'),
            headers: const {'Accept': 'application/json'},
          )
          .timeout(const Duration(seconds: 8));

      if (res.statusCode != 200) return null;

      final isi = jsonDecode(res.body) as Map<String, dynamic>;
      final nama = (isi['name'] as String?)?.trim();
      if (nama == null || nama.isEmpty) return null;

      final identitas = IdentitasSekolah(
        nama: nama,
        inisial: (isi['short_name'] as String?)?.trim() ?? 'SIM',
        logoUrl: _logoTerbesar(isi['icons']),
      );

      await identitas._simpan();
      return identitas;
    } catch (_) {
      // Server mati, alamat salah, atau jawabannya bukan JSON — semuanya
      // berarti hal yang sama di sini: pakai saja yang lama.
      return null;
    }
  }

  /// Ikon paling besar dari daftar manifest, supaya tidak buram saat
  /// ditampilkan pada kotak 64px di layar beresolusi tinggi.
  static String? _logoTerbesar(dynamic ikon) {
    if (ikon is! List) return null;

    String? terpilih;
    var terbesar = 0;

    for (final butir in ikon) {
      if (butir is! Map) continue;

      final src = butir['src'];
      if (src is! String || src.isEmpty) continue;

      // "512x512" -> 512. Bentuk lain diperlakukan sebagai ukuran 0,
      // jadi tetap terpakai bila tidak ada pembanding yang lebih baik.
      final ukuran = int.tryParse(
            (butir['sizes'] as String? ?? '').split('x').first,
          ) ??
          0;

      if (terpilih == null || ukuran > terbesar) {
        terpilih = src;
        terbesar = ukuran;
      }
    }

    return terpilih;
  }

  Future<void> _simpan() async {
    final p = await SharedPreferences.getInstance();
    await p.setString(_kunciNama, nama);
    await p.setString(_kunciInisial, inisial);
    if (logoUrl == null) {
      await p.remove(_kunciLogo);
    } else {
      await p.setString(_kunciLogo, logoUrl!);
    }
  }
}
