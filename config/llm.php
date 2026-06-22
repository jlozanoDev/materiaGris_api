<?php

return [
    'provider' => env('LLM_PROVIDER', 'openai'),
    'api_key' => env('LLM_API_KEY', ''),
    'model' => env('LLM_MODEL', 'gpt-4o'),
    'base_url' => env('LLM_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => (int) env('LLM_TIMEOUT', 30),
    'retry_attempts' => (int) env('LLM_RETRY_ATTEMPTS', 1),
];
