import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';
import 'package:local_auth_android/local_auth_android.dart';

/// MASUK DENGAN SIDIK JARI.
///
/// =====================================================================
/// APA YANG DISIMPAN, DAN DI MANA
/// =====================================================================
/// Layanan biasa memang TIDAK pernah menyimpan kata sandi — lihat
/// catatan di Layanan. Fitur ini adalah pengecualian yang disengaja,
/// dan hanya berlaku bila pengguna sendiri menyalakannya.
///
/// Supaya sidik jari bisa menggantikan pengetikan kata sandi, kata
/// sandinya harus ada di suatu tempat. Yang dipakai di sini adalah
/// flutter_secure_storage, yang di Android menyimpannya di
/// EncryptedSharedPreferences dengan kunci yang dijaga Android Keystore
/// — bukan di SharedPreferences biasa yang isinya terbaca polos.
///
/// Kredensial itu baru dibaca SESUDAH sidik jari cocok. Aplikasi lain
/// tidak bisa membacanya, dan mencabutnya cukup lewat tombol "Lupakan
/// sidik jari" di layar masuk.
///
/// =====================================================================
/// SEJUJURNYA: BATASNYA
/// =====================================================================
/// Pada ponsel yang sudah di-root, penyimpanan mana pun bisa dibongkar.
/// Yang dijamin mekanisme ini adalah: kata sandi tidak tergeletak polos,
/// dan tidak bisa dipakai tanpa sidik jari pemiliknya.
class Biometrik {
  static final _auth = LocalAuthentication();
  static const _brankas = FlutterSecureStorage();

  static const _kunci = 'kredensial_biometrik';

  static const _pesanAndroid = AndroidAuthMessages(
    signInTitle: 'Masuk ke SIM-SPENGA',
    signInHint: 'Tempelkan sidik jari Anda',
    cancelButton: 'Batal',
  );

  /// Apakah ponsel ini punya sidik jari/wajah yang siap dipakai.
  ///
  /// Dua pemeriksaan, bukan satu: perangkat bisa saja mendukung biometrik
  /// tetapi pemiliknya belum mendaftarkan sidik jari apa pun.
  static Future<bool> tersedia() async {
    try {
      if (!await _auth.isDeviceSupported()) return false;
      return await _auth.canCheckBiometrics;
    } catch (_) {
      // Di lingkungan tanpa kanal platform (mis. uji widget) pemanggilan
      // ini melempar jenis lain — diperlakukan sebagai "tidak tersedia".
      return false;
    }
  }

  /// Sudahkah pengguna menyalakan masuk dengan sidik jari di ponsel ini?
  static Future<bool> aktif() async {
    try {
      return await _brankas.read(key: _kunci) != null;
    } catch (_) {
      return false;
    }
  }

  /// Minta pengguna membuktikan dirinya. Mengembalikan true bila cocok.
  static Future<bool> minta(String alasan) async {
    try {
      return await _auth.authenticate(
        localizedReason: alasan,
        biometricOnly: true,
        persistAcrossBackgrounding: true,
        authMessages: const [_pesanAndroid],
      );
    } catch (_) {
      return false;
    }
  }

  /// Simpan kredensial supaya lain kali cukup sidik jari.
  static Future<void> simpan(String email, String sandi) async {
    await _brankas.write(
      key: _kunci,
      value: jsonEncode({'email': email, 'sandi': sandi}),
    );
  }

  /// Kredensial tersimpan, atau null bila belum pernah disimpan.
  ///
  /// HANYA panggil sesudah [minta] mengembalikan true.
  static Future<({String email, String sandi})?> ambil() async {
    try {
      final isi = await _brankas.read(key: _kunci);
      if (isi == null) return null;

      final peta = jsonDecode(isi) as Map<String, dynamic>;
      final email = peta['email'] as String?;
      final sandi = peta['sandi'] as String?;
      if (email == null || sandi == null) return null;

      return (email: email, sandi: sandi);
    } catch (_) {
      // Isi rusak (mis. format lama) diperlakukan seperti belum ada.
      return null;
    }
  }

  /// Cabut. Dipakai saat pengguna menolak, atau saat kata sandinya
  /// ternyata sudah diganti di server sehingga yang tersimpan basi.
  static Future<void> lupakan() async {
    try {
      await _brankas.delete(key: _kunci);
    } catch (_) {
      // Tidak ada yang bisa dilakukan bila brankasnya sendiri bermasalah.
    }
  }
}
