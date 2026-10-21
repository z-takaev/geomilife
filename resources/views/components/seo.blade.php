@php
    $seo = seo();
    $schemas = $seo->getSchemas();
    $structuredData =
        $schemas === []
            ? null
            : json_encode(
                [chr(64) . 'context' => 'https://schema.org', chr(64) . 'graph' => $schemas],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_HEX_TAG |
                    JSON_HEX_AMP |
                    JSON_HEX_APOS |
                    JSON_HEX_QUOT |
                    JSON_THROW_ON_ERROR,
            );
@endphp

<title>{{ $seo->getTitle() }}</title>
@if (filled($seo->getDescription()))
    <meta name="description" content="{{ $seo->getDescription() }}">
@endif
<link rel="canonical" href="{{ $seo->getCanonicalUrl() }}">

<meta property="og:title" content="{{ $seo->getTitle() }}">
@if (filled($seo->getDescription()))
    <meta property="og:description" content="{{ $seo->getDescription() }}">
@endif
@if (filled($seo->getImage()))
    <meta property="og:image" content="{{ $seo->getImage() }}">
@endif
<meta property="og:url" content="{{ $seo->getCanonicalUrl() }}">
<meta property="og:type" content="{{ $seo->getType() }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="{{ $seo->getLocale() }}">

@if ($structuredData !== null)
    <script type="application/ld+json">
        {!! $structuredData !!}
    </script>
@endif
