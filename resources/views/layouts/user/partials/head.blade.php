<head>
    <!-- ═════════════════════════ -->
    <meta charset="utf-8">
    <title>{{ env('APP_NAME') }} | @yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="name" content="projectX">
    <meta name="description" content="We bring the best and Selected Creative Goods for accelerating your creativity and productivity. We will add Best free Creative Goods Here, which Collected from leading Creative Marketplaces">
    <meta name="author" content="projectX">
    <meta name="copyright" content="projectX">
    <meta name="keywords" content="projectX, creative marketplace, premium creative goods, wordpress themes, premium fonts, free fonts, vectors for craft works,  premium templates, bootstrap, Webhance Studio network, Webhance Studio inc, html5, web devlopment, jquery animations, css3, jQuery, parallax, minimalist website, interactive html5, animated html5 websites, web design india, projectX.net, premium web development," />
    <!-- ═════════════════════════ -->
    <!-- Standard Favicon-->
    <link rel="shortcut icon" href="{{ asset('assets/user/images/golficon.ico') }}">
    <!-- Standard iPhone Touch Icon-->
    <link rel="apple-touch-icon" sizes="57x57" href="images/touchicons/apple-touch-icon-57-precomposed" />
    <!-- Retina iPhone Touch Icon-->
    <link rel="apple-touch-icon" sizes="114x114" href="assets/touchicons/apple-touch-icon-114-precomposed" />
    <!-- Standard iPad Touch Icon-->
    <link rel="apple-touch-icon" sizes="72x72" href="assets/touchicons/apple-touch-icon-72-precomposed" />
    <!-- Retina iPad Touch Icon-->
    <link rel="apple-touch-icon" sizes="144x144" href="assets/touchicons/apple-touch-icon-144-precomposed" />
    <!-- ═════════════════════════ -->
    <!-- Bootstrap CSS Files -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <!-- ═════════════════════════ -->
    <!--  CSS Files -->
    <!-- ═════════════════════════ -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/user/stylesheets/spaces.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/user/stylesheets/focal.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/user/stylesheets/mobile.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/user_style.css') }}">
    <!-- ═════════════════════════ -->
    <!--  Google Fonts -->
    <!-- ═════════════════════════ -->

    <!-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;400;700&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700&display=swap" rel="stylesheet">
    @stack('head-scripts')
</head>
