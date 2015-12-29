<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OAuth Tool | Helps you create access tokens for your development environment</title>
    <link href="//cdnjs.cloudflare.com/ajax/libs/foundation/5.5.2/css/foundation.min.css" rel="stylesheet" />
    <style>section { padding: 3% 0; }</style>
    @yield('styles')
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/foundation/5.5.2/js/foundation.min.js"></script>
    @yield('scripts')
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-1132651-18', 'auto');
      ga('send', 'pageview');

    </script>
</head>
<body>
    @yield('content')
</body>
</html>
