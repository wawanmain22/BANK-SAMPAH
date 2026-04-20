<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>{{ ucfirst($purposeLabel) }}</title>
</head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, Arial, sans-serif; background: #f5f5f4; margin: 0; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
        <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; color: #16a34a; font-weight: 700;">
            Bank Sampah
        </div>

        <h1 style="font-size: 20px; margin: 8px 0 16px; color: #0f172a;">
            Kode {{ $purposeLabel }} Anda
        </h1>

        <p style="color: #475569; line-height: 1.5; margin: 0 0 24px;">
            Gunakan kode berikut untuk {{ $purposeLabel }}. Kode berlaku {{ $ttl }} menit.
        </p>

        <div style="background: #f1f5f9; border: 2px solid #16a34a; border-radius: 8px; padding: 20px; text-align: center; margin: 0 0 24px;">
            <div style="font-family: 'SF Mono', Menlo, monospace; font-size: 36px; font-weight: 700; color: #0f172a; letter-spacing: 8px;">
                {{ $code }}
            </div>
        </div>

        <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin: 0 0 8px;">
            Jangan berikan kode ini kepada siapapun. Tim Bank Sampah tidak akan pernah meminta kode ini lewat telepon atau WhatsApp.
        </p>

        <p style="color: #94a3b8; font-size: 12px; line-height: 1.5; margin: 16px 0 0;">
            Jika Anda tidak meminta kode ini, abaikan email ini — akun Anda tetap aman.
        </p>
    </div>
</body>
</html>
