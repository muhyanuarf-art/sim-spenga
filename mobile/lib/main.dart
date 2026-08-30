import 'package:flutter/material.dart';

import 'layar_masuk.dart';

void main() {
  runApp(const AplikasiSimSpenga());
}

class AplikasiSimSpenga extends StatelessWidget {
  const AplikasiSimSpenga({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SIM-SPENGA',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1C68F2)),

        // Ukuran huruf dan tinggi tombol dinaikkan dari bawaan Material.
        // Pemakainya guru, sebagian sudah berumur, dan sebagian membuka
        // aplikasi ini di sela mengajar sambil berdiri — sasaran sentuh
        // yang kecil membuatnya salah tekan.
        inputDecorationTheme: const InputDecorationTheme(
          contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 18),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            minimumSize: const Size.fromHeight(48),
            textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
        ),
      ),
      home: const LayarMasuk(),
    );
  }
}
