<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>The spring collection is here</title>
    </head>
    <body style="margin: 0; padding: 40px 16px; background: #fafaf9; color: #292524; font-family: Arial, sans-serif;">
        <table role="presentation" style="width: 100%; max-width: 560px; margin: 0 auto; border-collapse: collapse;">
            <tr><td style="padding: 0 0 28px; font-size: 14px; font-weight: 700; letter-spacing: 2px;">STUDIO GOODS</td></tr>
            <tr><td style="padding: 36px 20px; border: 1px solid #e7e5e4; border-radius: 12px; background: #fff;">
                <h1 style="margin: 0 8px 12px; font-size: 28px; line-height: 1.2;">The spring collection is here.</h1>
                <p style="margin: 0 8px 28px; font-size: 15px; line-height: 1.7;">Four new colors for the pieces you reach for every day.</p>
                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                    @foreach (array_chunk($products, 2) as $row)
                        <tr>
                            @foreach ($row as $product)
                                <td style="width: 50%; padding: 0 8px 24px; vertical-align: top;">
                                    <img src="{{ $product['image'] }}" width="236" height="160" style="display: block; width: 100%; height: auto; border-radius: 8px;" />
                                    <p style="margin: 12px 0 4px; font-size: 14px; font-weight: 700;">{{ $product['name'] }}</p>
                                    <p style="margin: 0; color: #c7c2bc; font-size: 13px;">{{ $product['color'] }} · {{ $product['price'] }}</p>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
                <a href="{{ url('/') }}" style="display: inline-block; margin: 4px 8px 0; padding: 12px 20px; border-radius: 8px; background: #292524; color: #fff; font-size: 14px; text-decoration: none;">Shop the collection</a>
            </td></tr>
            <tr><td style="padding: 24px 0;"><a href="{{ url('/') }}"><img src="{{ $icon }}" width="20" height="20" /></a></td></tr>
        </table>
    </body>
</html>
