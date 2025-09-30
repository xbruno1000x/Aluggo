<?php

return [
    // Provider: 'bacen' to use BCData/SGS, or 'config' to use fixed annual rates
    'provider' => env('FINANCE_PROVIDER', 'bacen'),

    // Bacen series default (you can override in .env):
    // Example: SELIC (series 11), IPCA (series 10844)
    'selic_series' => env('FINANCE_SELIC_SERIES', '11'),
    'ipca_series' => env('FINANCE_IPCA_SERIES', '10844'),

    // Bacen base URL (default BCData/SGS)
    'bacen_base' => env('FINANCE_API_BASE_URL', 'https://api.bcb.gov.br/dados/serie/bcdata.sgs'),
];
