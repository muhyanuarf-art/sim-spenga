import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Hasil percobaan masuk.
///
/// Sengaja memisahkan "berhasil" dari "pesan": layar masuk perlu tahu
/// keduanya, dan menggabungkannya jadi satu String membuat layar itu harus
/// menebak-nebak isi pesan untuk tahu apakah boleh lanjut.
class HasilMasuk {
  final bool berhasil;
  final String pesan;
  final String? urlMasuk;
  final String? nama;
  final String? peran;

  const HasilMasuk({
    required this.berhasil,
    required this.pesan,
    this.urlMasuk,
    this.nama,
    this.peran,
  });
}

/// Penghubung ke server SIM-SPENGA.
///
/// =====================================================================
/// KENAPA TIDAK ADA TOKEN DI SINI
/// =====================================================================
/// Aplikasi ini tidak memanggil puluhan endpoint JSON — seluruh isinya
/// adalah aplikasi web yang ditampilkan lewat WebView. Yang dibutuhkan
/// hanya SATU langkah: menukar email + kata sandi dengan sebuah tautan
/// masuk-sekali, lalu membuka tautan itu di WebView supaya sesinya
/// terbentuk seperti login biasa.
///
/// Akibatnya kata sandi TIDAK PERNAH disimpan di ponsel. Yang tersimpan
/// hanya alamat server dan (bila dipilih) alamat surel — supaya tidak
/// perlu diketik ulang tiap hari.
class Layanan {
  /// Alamat server bawaan. Diisi saat pemasangan di sekolah; pengguna
  /// tetap bisa menggantinya dari aplikasi bila alamatnya berubah.
  static const String alamatBawaan = 'http://192.168.1.10';

  static const _kunciAlamat = 'alamat_server';
  static const _kunciEmail = 'email_terakhir';

  /// Alamat server yang tersimpan, tanpa garis miring di ujung.
  static Future<String> alamatServer() async {
    final p = await SharedPreferences.getInstance();
    final tersimpan = p.getString(_kunciAlamat);
    return _rapikan(tersimpan == null || tersimpan.isEmpty ? alamatBawaan : tersimpan);
  }

  static Future<void> simpanAlamatServer(String alamat) async {
    final p = await SharedPreferences.getInstance();
    await p.setString(_kunciAlamat, _rapikan(alamat));
  }

  static Future<String?> emailTerakhir() async {
    final p = await SharedPreferences.getInstance();
    return p.getString(_kunciEmail);
  }

  static Future<void> simpanEmail(String? email) async {
    final p = await SharedPreferences.getInstance();
    if (email == null || email.isEmpty) {
      await p.remove(_kunciEmail);
    } else {
      await p.setString(_kunciEmail, email);
    }
  }

  /// Buang spasi dan garis miring di ujung, dan tambahkan http:// bila
  /// pengguna hanya mengetik alamat IP.
  static String _rapikan(String alamat) {
    var a = alamat.trim();
    while (a.endsWith('/')) {
      a = a.substring(0, a.length - 1);
    }
    if (a.isNotEmpty && !a.startsWith('http://') && !a.startsWith('https://')) {
      a = 'http://$a';
    }
    return a;
  }

  /// Tukar kredensial dengan tautan masuk-sekali.
  static Future<HasilMasuk> masuk(String email, String sandi) async {
    final alamat = await alamatServer();

    http.Response res;
    try {
      res = await http
          .post(
            Uri.parse('$alamat/aplikasi/masuk'),
            headers: const {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: jsonEncode({'email': email, 'password': sandi}),
          )
          .timeout(const Duration(seconds: 20));
    } catch (e) {
      // Pesan galat mentah dari paket http tidak bisa dipahami guru.
      // Yang berguna baginya adalah apa yang harus diperiksa.
      return HasilMasuk(
        berhasil: false,
        pesan: 'Tidak bisa menghubungi server di $alamat.\n\n'
            'Periksa: ponsel tersambung wifi sekolah, alamat servernya benar, '
            'dan komputer servernya menyala.',
      );
    }

    Map<String, dynamic> isi;
    try {
      isi = jsonDecode(res.body) as Map<String, dynamic>;
    } catch (_) {
      isi = {};
    }

    if (res.statusCode == 200 && isi['url_masuk'] != null) {
      return HasilMasuk(
        berhasil: true,
        pesan: 'Berhasil masuk.',
        urlMasuk: isi['url_masuk'] as String,
        nama: isi['nama'] as String?,
        peran: isi['peran'] as String?,
      );
    }

    // 422 dari Laravel bisa berbentuk {"errors": {"email": ["..."]}}
    if (isi['errors'] is Map) {
      final errors = isi['errors'] as Map;
      final pertama = errors.values.first;
      if (pertama is List && pertama.isNotEmpty) {
        return HasilMasuk(berhasil: false, pesan: pertama.first.toString());
      }
    }

    if (isi['pesan'] is String) {
      return HasilMasuk(berhasil: false, pesan: isi['pesan'] as String);
    }

    // Lisensi belum aktif untuk alamat ini menghasilkan 403 dari
    // middleware, sebelum controller sempat menjawab.
    if (res.statusCode == 403) {
      return const HasilMasuk(
        berhasil: false,
        pesan: 'Server menolak permintaan. Bila ini pemasangan baru, '
            'aplikasi di server kemungkinan belum diaktifkan untuk alamat ini. '
            'Hubungi Admin sekolah.',
      );
    }

    return HasilMasuk(
      berhasil: false,
      pesan: 'Server membalas dengan kode ${res.statusCode}. Hubungi Admin sekolah.',
    );
  }
}
