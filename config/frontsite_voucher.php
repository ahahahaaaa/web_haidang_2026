<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tour Voucher CTA
    |--------------------------------------------------------------------------
    |
    | Empty, "0", "false", or "off" disables the CTA. The default
    | "voucher-du-lich" tag shows the latest active campaign on the voucher
    | landing page. A campaign slug can be used to pin a specific campaign.
    |
    */

    'tour_cta_tag' => env('FRONTSITE_TOUR_VOUCHER_CTA_TAG', 'voucher-du-lich'),
];
