<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Bank Sampah') : config('app.name', 'Bank Sampah') }}
</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=lato:400,500,700,900" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
