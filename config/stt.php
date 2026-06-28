<?php

return [
    'provider' => env('STT_PROVIDER', 'opencode'),
    'api_key' => env('STT_API_KEY', env('LLM_API_KEY', '')),
    'model' => env('STT_MODEL', 'mimo-v2.5'),
    'base_url' => env('STT_BASE_URL', env('LLM_BASE_URL', 'https://opencode.ai/zen/go/v1')),
    'timeout' => (int) env('STT_TIMEOUT', 120),
    'language' => env('STT_LANGUAGE'),
];
