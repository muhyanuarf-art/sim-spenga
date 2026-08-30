<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tidak Bisa Masuk</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0;
               min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #f1f5f9; color: #0f172a; padding: 24px; }
        .kotak { background: #fff; border-radius: 18px; padding: 28px 24px; max-width: 420px;
                 text-align: center; box-shadow: 0 10px 30px -12px rgba(15,23,42,.25); }
        .ikon { width: 56px; height: 56px; border-radius: 16px; background: #fee2e2; color: #dc2626;
                display: flex; align-items: center; justify-content: center; font-size: 26px;
                margin: 0 auto 16px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { font-size: 15px; line-height: 1.6; color: #475569; margin: 0; }
    </style>
</head>
<body>
    <div class="kotak">
        <div class="ikon">&#9888;</div>
        <h1>Tidak Bisa Masuk</h1>
        <p>{{ $pesan }}</p>
    </div>
</body>
</html>
