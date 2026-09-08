@if (($googleRecaptchaV3Enabled ?? false) && filled($googleRecaptchaV3SiteKey ?? null))
    <input type="hidden" name="g-recaptcha-response" value="" data-google-recaptcha-v3-token>
@endif
