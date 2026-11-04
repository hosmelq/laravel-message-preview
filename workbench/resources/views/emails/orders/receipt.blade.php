<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="color-scheme" content="light dark" />
        <meta name="supported-color-schemes" content="light dark" />
        <title>Your order is confirmed</title>
        <style>
            @media (prefers-color-scheme: dark) {
                .page { background: #1c1917 !important; color: #e7e5e4 !important; }
                .card { background: #292524 !important; border-color: #44403c !important; }
                .muted { color: #a8a29e !important; }
            }
        </style>
    </head>
    <body class="page" style="margin: 0; padding: 40px 16px; background: #fafaf9; color: #292524; font-family: Arial, sans-serif;">
        <table role="presentation" style="width: 100%; max-width: 560px; margin: 0 auto; border-collapse: collapse;">
            <tr><td style="padding: 0 0 28px; font-size: 14px; font-weight: 700; letter-spacing: 2px;">STUDIO GOODS</td></tr>
            <tr><td class="card" style="padding: 36px 28px; border: 1px solid #e7e5e4; border-radius: 12px; background: #fff;">
                <p class="muted" style="margin: 0 0 12px; color: #78716c; font-size: 12px; letter-spacing: 1px;">ORDER #SG-2048</p>
                <h1 style="margin: 0 0 20px; font-size: 28px; line-height: 1.2;">Good things are on the way.</h1>
                <p style="font-size: 15px; line-height: 1.7;">Thanks for your order, {{ $customerName }}. We are getting everything ready and will let you know when it ships.</p>
                <table role="presentation" style="width: 100%; margin: 28px 0; border-collapse: collapse; font-size: 14px;">
                    <tr><td style="padding: 16px 0; border-top: 1px solid #e7e5e4;">Everyday notebook · Sand</td><td style="text-align: right; border-top: 1px solid #e7e5e4;">$24.00</td></tr>
                    <tr><td style="padding: 16px 0; border-top: 1px solid #e7e5e4; font-weight: 700;">Total</td><td style="text-align: right; border-top: 1px solid #e7e5e4; font-weight: 700;">$24.00</td></tr>
                </table>
                @if ($note !== null)
                    <p class="card" style="padding: 16px; background: #f5f5f4; border-radius: 8px; font-size: 14px; line-height: 1.6;">{{ $note }}</p>
                @endif
                <p style="margin: 28px 0 0; font-size: 14px; line-height: 1.6;">Made for your everyday.<br />The Studio Goods team</p>
            </td></tr>
            <tr><td style="padding: 24px 0; color: #a8a29e; font-size: 12px; line-height: 1.6;">Questions about your order? Reply to this email.<br />Studio Goods · Thoughtfully made essentials</td></tr>
        </table>
    </body>
</html>
