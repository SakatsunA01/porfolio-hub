<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    @if(!empty($imageUrl))
        <meta property="og:image" content="{{ $imageUrl }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if(!empty($imageUrl))
        <meta name="twitter:image" content="{{ $imageUrl }}">
    @endif

    <meta http-equiv="refresh" content="0;url={{ $productUrl }}">
    <script>
      window.location.replace(@json($productUrl));
    </script>
</head>
<body>
    <p>Redirigiendo al producto...</p>
    <p><a href="{{ $productUrl }}">Abrir producto</a></p>
</body>
</html>
