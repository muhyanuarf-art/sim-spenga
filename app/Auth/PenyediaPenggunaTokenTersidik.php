<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * "INGAT SAYA" YANG TIDAK BISA DIRAKIT ULANG DARI DUMP DATABASE.
 *
 * =====================================================================
 * MASALAH PADA PERILAKU BAWAAN LARAVEL
 * =====================================================================
 * Cookie "Ingat saya" berisi `id|remember_token|awalan hash sandi`,
 * dienkripsi dengan APP_KEY. Laravel menyimpan remember_token itu POLOS
 * di kolom `remember_token`, lalu membandingkannya apa adanya:
 *
 *     hash_equals($rememberToken, $token)   // EloquentUserProvider
 *
 * Akibatnya, siapa pun yang suatu saat memegang SALINAN DATABASE beserta
 * APP_KEY — misalnya dari berkas backup yang hilang, atau hard disk lama
 * yang dibuang — memiliki ketiga bahan cookie itu sekaligus. Ia bisa
 * merakit sendiri cookie yang sah untuk akun mana pun, termasuk Admin,
 * lalu masuk TANPA PERNAH tahu kata sandinya.
 *
 * Kata sandi yang sudah di-bcrypt tidak menolong, karena kata sandinya
 * memang tidak diperlukan dalam serangan ini.
 *
 * =====================================================================
 * PERBAIKANNYA — SAMA SEPERTI KATA SANDI
 * =====================================================================
 * Yang disimpan di database bukan lagi tokennya, melainkan SIDIKNYA.
 * Yang dikirim ke peramban tetap token aslinya.
 *
 *   di cookie   : token asli (60 karakter acak)
 *   di database : hash('sha256', token asli)
 *
 * Dump database karena itu tidak lagi cukup: dari sidik tidak ada jalan
 * kembali ke tokennya. Fiturnya sendiri tetap utuh — pengguna tetap
 * dapat mencentang "Ingat saya" dan tetap dikenali saat kembali.
 *
 * SHA-256 polos sudah memadai di sini, tidak perlu bcrypt: tokennya
 * dibuat mesin, 60 karakter acak, jadi tidak ada yang bisa ditebak lewat
 * kamus. Yang perlu dicegah hanyalah pembacaan balik, dan itu sudah
 * dijamin fungsi satu arah.
 *
 * =====================================================================
 * SAAT DIPASANG PERTAMA KALI
 * =====================================================================
 * Token lama yang terlanjur tersimpan polos tidak akan pernah cocok
 * dengan sidik, sehingga pemiliknya sekadar diminta masuk sekali lagi —
 * dan sejak itu tokennya tersimpan aman. Tidak ada yang perlu dimigrasi.
 */
class PenyediaPenggunaTokenTersidik extends EloquentUserProvider
{
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $model = $this->createModel();

        $ditemukan = $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();

        if (! $ditemukan) {
            return null;
        }

        $tersimpan = $ditemukan->getRememberToken();

        if (! $tersimpan) {
            return null;
        }

        // Yang dibandingkan: sidik milik database vs sidik dari token
        // yang dibawa cookie. hash_equals dipertahankan supaya lama
        // pembandingan tidak membocorkan seberapa dekat sebuah tebakan.
        return hash_equals($tersimpan, self::sidik($token)) ? $ditemukan : null;
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token)
    {
        // Ke database: sidiknya.
        $user->setRememberToken(self::sidik($token));

        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->save();
        $user->timestamps = $timestamps;

        // Ke memori: dikembalikan ke nilai aslinya, karena SessionGuard
        // membaca atribut ini untuk menyusun cookie yang dikirim ke
        // peramban. Tanpa baris ini, yang terkirim justru sidiknya —
        // cookie tetap "berfungsi", tetapi database kembali menyimpan
        // nilai yang sama dengan isi cookie, dan seluruh gunanya hilang.
        $user->setRememberToken($token);
    }

    private static function sidik(string $token): string
    {
        return hash('sha256', $token);
    }
}
