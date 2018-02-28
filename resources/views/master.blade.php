<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Splinter</title>
    <link rel="icon" type="image/png" href="/images/splinter-logo.png">
    <link rel="stylesheet" href="{{ mix("css/bulma.css") }}">
    <script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
</head>
<body>
<nav class="navbar is-dark">
    <div class="container">
        <div class="navbar-brand">
            <a class="navbar-item" href="https://bulma.io">
                <img src="/images/splinter-word-logo.png" alt="Splinter: a modern Crypto Backtesting Framework"
                     width="112" height="28">
            </a>
            <div class="navbar-burger burger" data-target="navbarExampleTransparentExample">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="navbar-menu">

            <div class="navbar-end">
                <a class="navbar-item" href="{!! action('HomeController@index') !!}">
                    Home
                </a>
                <a class="navbar-item" href="/documentation/overview/start/">
                    Backtests
                </a>
            </div>
        </div>
    </div>
</nav>
<section class="section">
    <div class="container">
        @yield('content')

    </div>
</section>
</body>
</html>