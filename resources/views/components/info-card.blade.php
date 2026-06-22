{{-- White rounded card with soft brand shadow. Used for step / info rows. --}}
<div {{ $attributes->merge(['class' => 'rounded-3xl border border-soft bg-white p-5 shadow-soft']) }}>
    {{ $slot }}
</div>
