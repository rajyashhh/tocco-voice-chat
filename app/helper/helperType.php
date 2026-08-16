<?php

const VARIABLES = [
    'level',
    'user_name',
    'agency',
    'salary',
    'agency_name',
    'family_name',
    'gift',
    'coins',
    'vip',
    'family_level',
    'ware_vip',
    'value',
    'reason',
    'room'
];
const TYPE_GIFT =
[
    1 => 'normal',
    2 => 'hot',
    3 => 'country',
    4 => 'Moment',
    5 => 'Famous gifts',
    6 => 'Lucky gifts',
    7 => 'events',
    9 => 'vip',
    10 => 'cp',

];

const TYPE_WARE =
[
    // 1 => 'Gemstone',
    3 => 'Card Scroll',
    4 => 'Avatar Frame',
    5 => 'Bubble Frame',
    6 => 'Entering Special Effects',
    7 => 'Microphone Aperture',
    8 => 'Badge',
    9 => 'NoKick',
    10 => 'Icon',
    11 => 'intro animation',
    12 => 'maple',
    13 => 'hide country',
    14 => 'vip gifts',
    15 => 'no pan',
    // 16 => 'hidden room',
    // 17 => 'anonymous man',
    // 18 => 'colored name',
    19 => 'profile visitors hide in',
    20 => 'hide last active',
    28 => 'profile frame',
    29 => 'being kicked',
    30 => 'anti ban',

];

const MORE_Used_WARE =
[

    4 => 'Avatar Frame',
    5 => 'Bubble Frame',
    6 => 'Entering Special Effects',
    7 => 'Microphone Aperture',
    8 => 'Badge',
    13 => 'hide country',
    16 => 'hidden room',
    // 17 => 'anonymous man',
    18 => 'colored name',
    19 => 'profile visitors hide in',
    20 => 'hide last active',
    28 => 'profile frame',
    29 => 'being kicked',
    30 => 'anti ban',
];

const WARE_DEDICATE =
[
    1 => 'Gemstone',
    3 => 'Card Scroll',
    4 => 'Avatar Frame',
    5 => 'Bubble Frame',
    6 => 'Entering Special Effects',
    7 => 'Microphone Aperture',
    8 => 'Badge',
    9 => 'NoKick',
    10 => 'Icon',
    11 => 'intro animation',
    12 => 'wapel',
    13 => 'hide country',
    14 => 'vip gifts',
    15 => 'no pan',
    16 => 'hidden room',
    17 => 'anonymous man',
    18 => 'colored name',
    19 => 'profile visitors hide in',
    20 => 'hide last active',
    21 => 'sound effect',
    22 => 'upload GIF image',
];

const SELECTED_USED_WARE =
[
    4 => 'Avatar Frame',
    5 => 'Bubble Frame',
    6 => 'Entering Special Effects',
    28 => 'profile frame',
];
function getTranslatedUsedWare()
{
    return [
        4  => __('Avatar Frame'),
        5  => __('Bubble Frame'),
        6  => __('Entering Special Effects'),
        28 => __('profile frame'),
    ];
}

function getTranslatedWare()
{
    return [
        1  => __('Gemstone'),
        3  => __('Card Scroll'),
        4  => __('Avatar Frame'),
        5  => __('Bubble Frame'),
        6  => __('Entering Special Effects'),
        7  => __('Microphone Aperture'),
        8  => __('Badge'),
        9  => __('NoKick'),
        10 => __('Icon'),
        11 => __('Intro Animation'),
        12 => __('Wapel'),
        13 => __('Hide Country'),
        14 => __('VIP Gifts'),
        15 => __('No Pan'),
        16 => __('Hidden Room'),
        17 => __('Anonymous Man'),
        18 => __('Colored Name'),
        19 => __('Profile Visitors Hidden'),
        20 => __('Hide Last Active'),
        21 => __('Sound Effect'),
        22 => __('Upload GIF Image'),
    ];
}

const PACK_USER =
[
    4 => 'Avatar Frame',
    5 => 'Bubble Frame',
    6 => 'Entering Special Effects',
    9 => 'NoKick',
    10 => 'Icon',
    12 => 'wapel',
    11 => 'intro animation',
    13 => 'hide country',
    14 => 'vip gifts',
    15 => 'no pan',
    16 => 'hidden room',
    17 => 'anonymous man',
    18 => 'colored name',
    19 => 'profile visitors hide in',
    20 => 'hide last active',
    21 => 'sound effect',
    22 => 'upload GIF image',
    25 => 'special uuid',
    28 => 'profile frame',
];

const  GET_TYPE_WARE =
[
    4 => 'purchase',
    // 6 => 'limited time purchase',

];

const  GET_TYPE_WARE_TYPES =
[
    4 => 'purchase',
    6 => 'limited time purchase',

];
