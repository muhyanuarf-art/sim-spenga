<?php

/*
| Pesan bawaan Laravel saat login. Halaman login aplikasi ini sudah memakai
| pesannya sendiri (lihat Auth\LoginController & Auth\OrangTuaLoginController),
| jadi yang paling sering terlihat dari berkas ini adalah 'throttle' —
| muncul kalau ada yang mencoba menebak kata sandi berulang kali.
*/

return [

    'failed' => 'Email atau kata sandi salah.',
    'password' => 'Kata sandi yang dimasukkan salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',

];
