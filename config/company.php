<?php

// Company details printed on delivery orders (and later quotations). Set in .env.
return [
    'name'        => env('COMPANY_NAME', 'Colossal Media'),
    'reg_no'      => env('COMPANY_REG_NO'),
    'address'     => env('COMPANY_ADDRESS'),       // use "\n" for line breaks
    'phone'       => env('COMPANY_PHONE'),
    'email'       => env('COMPANY_EMAIL', env('MAIL_FROM_ADDRESS')),
    'website'     => env('COMPANY_WEBSITE'),
    'logo'        => env('COMPANY_LOGO', 'assets/img/branding/login-logo.png'), // path under public/
];
