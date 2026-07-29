<?php

return [
    // OpenAI API Configuration
    'api_key' => 'sk-proj-opjC93Q6UyOurVirEw0fMOUsYh9vpzWOzVpUczP5gkJYESfD41JE_O-kTx3Or5aN_TqllwG2mPT3BlbkFJ_aqPywgt_cffqm9qaGMIA6kKnB02kDenj7H8lyfULQ2soelXhfbJsfeh5xCQUxA6_6LRasvWwA',
    'base_url' => 'https://api.openai.com/v1',
    'organization' => null, // Optional: your organization ID
    'project' => 'drivejob', // Project name

    // Available Models (including ChatGPT-5/o1)
    'available_models' => [
        'o1-preview' => [
            'name' => 'ChatGPT-5 (o1-preview)',
            'description' => 'Πιο προηγμένο μοντέλο με βελτιωμένη λογική',
            'max_tokens' => 32768,
            'supports_streaming' => false,
            'cost_per_1k_input' => 0.015,
            'cost_per_1k_output' => 0.060
        ],
        'o1-mini' => [
            'name' => 'ChatGPT-5 Mini (o1-mini)',
            'description' => 'Γρηγορότερη έκδοση του o1',
            'max_tokens' => 65536,
            'supports_streaming' => false,
            'cost_per_1k_input' => 0.003,
            'cost_per_1k_output' => 0.012
        ],
        'gpt-4o' => [
            'name' => 'GPT-4o',
            'description' => 'Βελτιωμένο GPT-4 με multimodal capabilities',
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'cost_per_1k_input' => 0.005,
            'cost_per_1k_output' => 0.015
        ],
        'gpt-4o-mini' => [
            'name' => 'GPT-4o Mini',
            'description' => 'Οικονομική έκδοση του GPT-4o',
            'max_tokens' => 16384,
            'supports_streaming' => true,
            'cost_per_1k_input' => 0.00015,
            'cost_per_1k_output' => 0.0006
        ],
        'gpt-4-turbo' => [
            'name' => 'GPT-4 Turbo',
            'description' => 'Γρήγορο και αποδοτικό GPT-4',
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'cost_per_1k_input' => 0.01,
            'cost_per_1k_output' => 0.03
        ],
        'gpt-4' => [
            'name' => 'GPT-4',
            'description' => 'Κλασικό GPT-4 μοντέλο',
            'max_tokens' => 8192,
            'supports_streaming' => true,
            'cost_per_1k_input' => 0.03,
            'cost_per_1k_output' => 0.06
        ],
        'gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'description' => 'Γρήγορο και οικονομικό μοντέλο',
            'max_tokens' => 4096,
            'supports_streaming' => true,
            'cost_per_1k_input' => 0.0015,
            'cost_per_1k_output' => 0.002
        ]
    ],

    // Model Configuration for Different Tasks
    'models' => [
        'matching' => 'o1-preview', // Χρήση ChatGPT-5 για matching
        'insights' => 'o1-mini',    // Χρήση ChatGPT-5 Mini για insights
        'analysis' => 'gpt-4o',     // Χρήση GPT-4o για analysis
        'general' => 'gpt-4o-mini'  // Χρήση GPT-4o Mini για γενικές εργασίες
    ],

    // Request Parameters
    'default_params' => [
        'max_tokens' => 2000,
        'temperature' => 0.7,
        'top_p' => 1.0,
        'frequency_penalty' => 0.0,
        'presence_penalty' => 0.0
    ],

    // Advanced Settings
    'timeout' => 60, // Increased for o1 models
    'max_retries' => 3,
    'retry_delay' => 1, // seconds

    // Rate Limiting
    'rate_limits' => [
        'o1-preview' => [
            'requests_per_minute' => 20,
            'tokens_per_minute' => 30000
        ],
        'o1-mini' => [
            'requests_per_minute' => 50,
            'tokens_per_minute' => 200000
        ],
        'gpt-4o' => [
            'requests_per_minute' => 500,
            'tokens_per_minute' => 30000
        ],
        'gpt-4o-mini' => [
            'requests_per_minute' => 1000,
            'tokens_per_minute' => 200000
        ]
    ],

    // Feature Flags
    'features' => [
        'streaming' => true,
        'function_calling' => true,
        'vision' => true, // For GPT-4o models
        'reasoning' => true, // For o1 models
        'cost_tracking' => true,
        'usage_analytics' => true
    ],

    // Prompt Templates
    'prompts' => [
        'matching_system' => 'Είσαι ένας ειδικός σύμβουλος καριέρας για οδηγούς με προηγμένες δυνατότητες λογικής. Αναλύεις προφίλ οδηγών και θέσεις εργασίας για να βρεις το καλύτερο ταίριασμα. Χρησιμοποιείς προηγμένη λογική για να αξιολογήσεις όλους τους παράγοντες.',
        'insights_system' => 'Είσαι ένας AI σύμβουλος που δημιουργεί χρήσιμες και προηγμένες συμβουλές για οδηγούς. Παρέχεις συγκεκριμένες και πρακτικές συμβουλές με βάση προηγμένη ανάλυση.',
        'analysis_system' => 'Είσαι ένας ειδικός αναλυτής για θέσεις εργασίας οδηγών με δυνατότητες multimodal ανάλυσης. Εξάγεις δομημένες πληροφορίες από περιγραφές θέσεων.'
    ]
];
