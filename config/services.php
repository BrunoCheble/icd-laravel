<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Study app (icd-chords, Python): reads songs and saves edited chord maps with this token.
    // The chord suggestion helper (icd-chords, server.py) on this computer: only set in a local .env, so the bars
    // grid offers "suggest the ? chords" here and never in production.
    // Local computer with production's database: the songs' audio files are played from production's server.
    'music' => [
        'audio_fallback_url' => env('AUDIO_FALLBACK_URL'),
    ],

    'chords_helper' => [
        'url' => env('CHORDS_HELPER_URL'),
        // The same, as the server reaches it (from Docker: http://host.docker.internal:8765).
        'internal_url' => env('CHORDS_HELPER_INTERNAL_URL', env('CHORDS_HELPER_URL')),
    ],

    'study' => [
        'token' => env('STUDY_API_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
