@if ($siteSettings->facebook_pixel_id)
    <noscript>
        <img
            alt=""
            height="1"
            src="https://www.facebook.com/tr?id={{ urlencode($siteSettings->facebook_pixel_id) }}&ev=PageView&noscript=1"
            style="display:none"
            width="1"
        >
    </noscript>
@endif
