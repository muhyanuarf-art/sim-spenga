import 'dart:io' show Platform;

import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';

/// Seluruh aplikasi SIM-SPENGA ditampilkan di sini.
///
/// =====================================================================
/// KENAPA WEBVIEW, BUKAN LAYAR NATIVE SATU PER SATU
/// =====================================================================
/// Aturan yang paling mudah salah kalau ditulis dua kali adalah aturan
/// periode: data per semester, mode lihat-saja, periode terkunci. Semua
/// itu sudah dijaga di server dan sudah teruji di sana. Menulis ulang
/// tiap layar secara native berarti menyalin aturan-aturan itu ke tempat
/// kedua yang harus ikut diperbarui setiap kali aturannya berubah — dan
/// tempat kedua itulah yang akan tertinggal.
///
/// Dengan WebView, seluruh fitur langsung berjalan hari ini, dan setiap
/// perbaikan di server otomatis sampai ke ponsel tanpa memasang ulang
/// aplikasi.
class LayarWeb extends StatefulWidget {
  final String urlMasuk;
  final String nama;
  final String peran;

  const LayarWeb({
    super.key,
    required this.urlMasuk,
    required this.nama,
    required this.peran,
  });

  @override
  State<LayarWeb> createState() => _LayarWebState();
}

class _LayarWebState extends State<LayarWeb> {
  late final WebViewController _c;
  bool _memuat = true;
  String? _galat;

  @override
  void initState() {
    super.initState();

    _c = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) {
              setState(() {
                _memuat = true;
                _galat = null;
              });
            }
          },
          onPageFinished: (url) {
            if (!mounted) return;
            setState(() => _memuat = false);

            // Sesi web bisa kedaluwarsa (bawaannya beberapa jam). Kalau
            // itu terjadi, server mengalihkan ke halaman login web —
            // yang bukan tempat pengguna aplikasi ini seharusnya berada.
            // Dikembalikan ke layar masuk native supaya jalannya tetap
            // satu: masuk lewat aplikasi, bukan lewat halaman web.
            if (url.contains('/login') || url.contains('/aktivasi')) {
              _kembaliKeMasuk(
                url.contains('/aktivasi')
                    ? 'Aplikasi di server belum diaktifkan untuk alamat ini. Hubungi Admin sekolah.'
                    : 'Sesi Anda sudah berakhir. Silakan masuk lagi.',
              );
            }
          },
          onWebResourceError: (e) {
            // Galat sumber daya kecil (gambar gagal dimuat) tidak perlu
            // mengganggu; hanya kegagalan halaman utama yang ditampilkan.
            if (e.isForMainFrame != true) return;
            if (mounted) {
              setState(() {
                _memuat = false;
                _galat = 'Halaman gagal dimuat.\n\nPeriksa sambungan wifi Anda, '
                    'lalu tekan Muat Ulang.';
              });
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.urlMasuk));
  }

  Future<void> _kembaliKeMasuk(String pesan) async {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(pesan), duration: const Duration(seconds: 4)),
    );
    Navigator.of(context).pop();
  }

  Future<void> _keluar() async {
    final yakin = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar dari aplikasi?'),
        content: const Text('Anda perlu memasukkan kata sandi lagi untuk masuk.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Keluar')),
        ],
      ),
    );

    if (yakin != true) return;

    // Cookie sesi HARUS dihapus. Tanpa ini, pengguna berikutnya di ponsel
    // yang sama masih memegang sesi milik pengguna sebelumnya.
    await WebViewCookieManager().clearCookies();
    if (mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      // Tombol kembali Android menelusuri riwayat halaman dulu; keluar
      // dari aplikasi hanya bila memang sudah di halaman paling awal.
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        if (await _c.canGoBack()) {
          await _c.goBack();
        } else {
          await _keluar();
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.nama.isEmpty ? 'SIM-SPENGA' : widget.nama,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                overflow: TextOverflow.ellipsis,
              ),
              if (widget.peran.isNotEmpty)
                Text(widget.peran, style: const TextStyle(fontSize: 12, color: Colors.white70)),
            ],
          ),
          backgroundColor: const Color(0xFF1C68F2),
          foregroundColor: Colors.white,
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'Muat Ulang',
              onPressed: () => _c.reload(),
            ),
            IconButton(
              icon: const Icon(Icons.logout),
              tooltip: 'Keluar',
              onPressed: _keluar,
            ),
          ],
        ),
        body: Stack(
          children: [
            // Di Android, WebView dipaksa memakai komposisi hibrida: penggambaran
            // diserahkan ke view Android asli, bukan disalin ke tekstur Flutter
            // setiap frame. Tanpa ini gulir terasa tersendat, padahal halaman
            // yang sama mulus di Chrome pada ponsel yang sama.
            Platform.isAndroid
                ? WebViewWidget.fromPlatformCreationParams(
                    params: AndroidWebViewWidgetCreationParams(
                      controller: _c.platform,
                      displayWithHybridComposition: true,
                    ),
                  )
                : WebViewWidget(controller: _c),
            if (_galat != null)
              Container(
                color: Colors.white,
                alignment: Alignment.center,
                padding: const EdgeInsets.all(28),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.wifi_off, size: 52, color: Color(0xFF94A3B8)),
                    const SizedBox(height: 14),
                    Text(
                      _galat!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 15, height: 1.5, color: Color(0xFF475569)),
                    ),
                    const SizedBox(height: 20),
                    FilledButton.icon(
                      onPressed: () {
                        setState(() => _galat = null);
                        _c.reload();
                      },
                      icon: const Icon(Icons.refresh),
                      label: const Text('Muat Ulang'),
                    ),
                  ],
                ),
              )
            else if (_memuat)
              const LinearProgressIndicator(minHeight: 3),
          ],
        ),
      ),
    );
  }
}
