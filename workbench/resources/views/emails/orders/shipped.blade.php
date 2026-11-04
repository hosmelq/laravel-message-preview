<x-mail::message>
# Your everyday essentials are on their way.

Hi Ana, your order SG-2048 has shipped in {{ $packages === 1 ? 'one package' : $packages.' packages' }}. Expect it to arrive in 3–5 business days.

<x-mail::button :url="url('/orders/SG-2048')">
Track your order
</x-mail::button>

Thanks for shopping with us,<br>
The Studio Goods team
</x-mail::message>
