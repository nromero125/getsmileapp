<?php

return [
    /*
     * Meta Cloud API credentials.
     * Get these from: https://developers.facebook.com → Your App → WhatsApp → API Setup
     */
    'token'           => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'webhook_token'   => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'dentaris_webhook'),

    'api_url' => 'https://graph.facebook.com/v21.0',

    /*
     * Default country code for phone normalization.
     * Dominican Republic = 1 (NANP)
     */
    'default_country_code' => env('WHATSAPP_COUNTRY_CODE', '1'),

    /*
     * Template names as registered in Meta Business Manager.
     * Language must match the approved language for each template.
     */
    'templates' => [
        'appointment_confirmation' => [
            'name'     => env('WA_TEMPLATE_CONFIRMATION', 'appointment_confirmation'),
            'language' => env('WA_TEMPLATE_LANGUAGE_CONFIRMATION', 'es'),
        ],
        'appointment_reminder' => [
            'name'     => env('WA_TEMPLATE_REMINDER', 'appointment_reminder'),
            'language' => env('WA_TEMPLATE_LANGUAGE_REMINDER', 'es'),
        ],
        'invoice_ready' => [
            'name'     => env('WA_TEMPLATE_INVOICE', 'invoice_ready'),
            'language' => env('WA_TEMPLATE_LANGUAGE_INVOICE', 'es'),
        ],
    ],
];
