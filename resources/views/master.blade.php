<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Splinter</title>
    <link rel="icon" type="image/png" href="/images/splinter-logo.png">
    <link rel="stylesheet" href="{{ mix("css/bulma.css") }}">
    <link rel="stylesheet" href="{{ mix("css/dataTables.bulma.css") }}">
    <link rel="stylesheet" href="{{ mix("css/app.css") }}">
    <script defer src="//use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
</head>
<body>
<nav class="navbar is-dark">
    <div class="container">
        <div class="navbar-brand">
            <a class="navbar-item" href="//bulma.io">
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
        <div class="content">
            @yield('content')
        </div>
    </div>
</section>

<script
  src="//code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="{{ mix("js/dataTables.bulma.min.js") }}"></script>
@yield('footer')
</body>
</html>