@if (($googleRecaptchaV3Enabled ?? false) && filled($googleRecaptchaV3SiteKey ?? null))
    <script>
        window.HaidangRecaptchaV3 = {
            enabled: true,
            siteKey: @json($googleRecaptchaV3SiteKey),
            defaultAction: 'frontsite_form',
        };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($googleRecaptchaV3SiteKey) }}" async defer></script>
@else
    <script>
        window.HaidangRecaptchaV3 = {
            enabled: false,
            siteKey: '',
            defaultAction: 'frontsite_form',
        };
    </script>
@endif
