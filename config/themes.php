<?php

/*
 * Admin-panel theme colors — FIXED by owner decision (no panel control).
 *
 * These are the byte-identical defaults previously injected per-request by the
 * RefreshThemeConfig middleware from the settings table. Every blade that reads
 * config('themes.*') (dynamic-style, header, menus, login pages, profiles...)
 * now resolves to these constants; the settings rows are ignored.
 */
return [
    'primaryColor'         => '#FF9428',
    'secondaryColor'       => '#1A1A1A',
    'greenColor'           => '#10b981',
    'textPrimaryColor'     => '#fdf8f8',
    'textSecondaryColor'   => '#c1b9b9',
    'panelTextColor'       => '#ffffff',
    'boxBackgroundColor'   => '#222222',
    'backgroundImage'      => '',
    'brandBackgroundImage' => '',
    'tableBackGroundColor' => '#c88213',
];
