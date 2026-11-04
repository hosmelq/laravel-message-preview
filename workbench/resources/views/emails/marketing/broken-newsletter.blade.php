<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            .products { display: flex; gap: 16px; }
            .product { position: relative; flex: 1; }
            .badge { position: absolute; top: 8px; left: 8px; }
            .links { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            a:hover { text-decoration: underline; }
            @media (prefers-color-scheme: dark) {
                body { background: #1c1917 !important; color: #f5f5f4 !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 40px 16px; background: #fafaf9; color: #292524; font-family: Arial, sans-serif;">
        <div style="max-width: 560px; margin: 0 auto;">
            <img src="images/logo.png" width="120" height="32" />
            <h1 style="margin: 24px 0 12px; font-size: 28px; line-height: 1.2;">This week at Studio Goods</h1>
            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6;">{{ __('newsletter.greeting') }}</p>
            <h3 style="margin: 0 0 12px; font-size: 18px;">New arrivals</h3>
            <div class="products">
                @foreach ($products as $product)
                    <div class="product">
                        <img src="{{ $product['image'] }}" width="260" height="160" style="display: block; width: 100%; height: auto; border-radius: 8px;" />
                        <span class="badge" style="padding: 2px 8px; border-radius: 999px; background: #fff; color: #a8a29e; font-size: 12px;">New</span>
                        <p style="margin: 12px 0 0; font-size: 14px; font-weight: 700;">{{ $product['name'] }}</p>
                    </div>
                @endforeach
            </div>
            <h2 style="margin: 28px 0 0;"></h2>
            <p style="margin: 16px 0; color: #d6d3d1; font-size: 13px;">Prices include tax.</p>
            <div class="links">
                <a href="{{ url('/sale') }}" style="color: #292524;">Shop the sale</a>
                <a href="https://shop.studio-goods.invalid/new" style="color: #292524;">See what is new</a>
                <a href="http://journal.studio-goods.invalid/spring" style="color: #292524;">Read the journal</a>
                <a href="/account" style="color: #292524;">Your account</a>
            </div>
            <p style="margin: 28px 0 8px; font-size: 12px;">
                <a href="#" style="color: #a8a29e;">{{ __('newsletter.unsubscribe') }}</a>
                <a href="" title="{{ __('newsletter.browser') }}" style="margin-left: 12px; color: #a8a29e;">View in browser</a>
            </p>
            <a href="{{ url('/') }}"><img src="{{ $icon }}" width="20" height="20" /></a>
            <p style="margin: 24px 0 0; color: #d6d3d1; font-size: 11px; line-height: 1.5;">{{ $legal }}</p>
        </div>
    </body>
</html>
