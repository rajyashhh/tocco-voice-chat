<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Color palette
    |--------------------------------------------------------------------------
    | Background colors for generated letter avatars. One is picked
    | deterministically per entity (crc32(seed) % count), so the same entity
    | always gets the same color. Curated to be pleasant and readable with
    | white text.
    */
    'palette' => [
        '#F44336', // red
        '#E91E63', // pink
        '#9C27B0', // purple
        '#673AB7', // deep purple
        '#3F51B5', // indigo
        '#2196F3', // blue
        '#0288D1', // light blue
        '#00897B', // teal
        '#388E3C', // green
        '#689F38', // light green
        '#F57C00', // orange
        '#E64A19', // deep orange
        '#5D4037', // brown
        '#455A64', // blue grey
        '#C2185B', // dark pink
        '#512DA8', // dark purple
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded user ids (system / official account)
    |--------------------------------------------------------------------------
    | The official/system account keeps the app logo as its avatar and must
    | never get a generated letter avatar. Provide its user id(s) here (comma
    | separated in env LETTER_AVATAR_EXCLUDED_USER_IDS).
    */
    'excluded_user_ids' => array_filter(array_map(
        'intval',
        explode(',', (string) env('LETTER_AVATAR_EXCLUDED_USER_IDS', ''))
    )),

];
