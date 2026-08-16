<?php

namespace Database\Seeders;

use App\Models\RoleCategory;
use App\Enums\PermissionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class PermissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permission_types')->truncate();
        DB::table('role_categories')->truncate();
        $defaultMethods = ['browse', 'create', 'delete', 'edit', 'show'];

        // Define your base categories
        $categories = [
            [
                'name' => 'Dashboard',
                'sort' => 1,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 1],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 1],
                    PermissionType::AREA_MANAGER->value => ['sort' => 1],
                ],
                'permissions' => [
                    [
                        'key' => 'dashboard',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['pay-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'pay-switch'],
                            PermissionType::SUPER_ADMIN->value => ['browse'],
                            PermissionType::AREA_MANAGER->value => ['browse'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Fast orders',
                'sort' => 2,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 2],
                ],
                'permissions' => [
                    [
                        'key' => 'request-backgrounds-image',
                        'except' => [],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => $defaultMethods,
                        ],
                    ],
                    [
                        'key' => 'bans',
                        'except' => ['show', 'edit'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'delete'],
                        ],
                    ],
                    [
                        'key' => 'close-room',
                        'except' => ['show', 'edit'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'delete',],
                        ],
                    ],
                    [
                        'key' => 'special-uuid-requests',
                        'except' => ['show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'delete', 'edit'],
                        ],
                    ],
                    [
                        'key' => 'edit-level',
                        'except' => ['create', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse',  'edit'],
                        ],
                    ],
                    [
                        'key' => 'gift-from-the-store',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['gift-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'gift-switch'],
                        ],
                    ],
                    [
                        'key' => 'gift-VIP',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['gift-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'gift-switch'],
                        ],
                    ],
                    [
                        'key' => 'gift-a-medal',
                        'except' => ['show'],
                        'additional' => ['gift-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'delete', 'edit', 'gift-switch'],
                        ],
                    ],
                ],
            ],

            [
                'name' => 'regions system',
                'sort' => 3,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 3],
                ],
                'permissions' => [
                    [
                        'key' => 'area-manager',
                        'except' => [],
                        'additional' => ['charge-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['charge-switch', 'create', 'edit', 'delete', 'show', 'browse'],

                        ],
                    ],
                    [
                        'key' => 'charge-to-area-manager',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],

                        ],
                    ],
                ],
            ],

            [
                'name' => 'countries system',
                'sort' => 4,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 4],
                    PermissionType::AREA_MANAGER->value => ['sort' => 2],
                ],
                'permissions' => [
                    [
                        'key' => 'superadmin',
                        'except' => [],
                        'additional' => ['charge-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete', 'charge-switch', 'show'],
                            PermissionType::AREA_MANAGER->value => ['browse', 'create', 'show', 'delete'],

                        ],
                    ],
                    [
                        'key' => 'superadmin-banners',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['add-switch', 'history-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'add-switch', 'history-switch'],

                        ],
                    ],
                    [
                        'key' => 'restore-super-admin',
                        'except' => ['show', 'create', 'edit', 'delete'],
                        'additional' => ['restore-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete'],

                        ],
                    ],
                    [
                        'key' => 'charge-to-superadmin',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['add-switch', 'history-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'add-switch', 'history-switch'],
                            // PermissionType::AREA_MANAGER->value => ['browse', 'add-switch', 'history-switch'],

                        ],
                    ],
                    [
                        'key' => 'superadmin-settings',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],

                        ],
                    ],
                    [
                        'key' => 'super-roles',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gifts Rewards',
                'sort' => 5,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 5],
                ],
                'permissions' => [
                    ['key' => 'super-package-reward', 'except' => ['show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['dedicate-switch', 'browse', 'create', 'edit', 'delete'],
                    ],],
                    ['key' => 'admin-reward', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['dedicate-switch', 'browse'],
                    ],],
                    ['key' => 'admin-reward-history', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse',],
                    ],],
                ],
            ],
            [
                'name' => 'Games',
                'sort' => 6,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 6],
                ],
                'permissions' => [
                    [
                        'key' => 'games',
                        'except' => [],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => $defaultMethods,
                        ],
                    ],
                    [
                        'key' => 'coin-game-users-report',
                        'except' => ['show', 'edit', 'create', 'delete'],
                        'additional' => ['details-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'details-switch'],
                        ],
                    ],
                    [
                        'key' => 'game-settings',
                        'except' => [],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => $defaultMethods,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'badge',
                'sort' => 7,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 7],
                ],
                'permissions' => [
                    ['key' => 'badges', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'dedicate-badges', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'dedicate-switch'],
                    ],],

                ],
            ],


            [
                'name' => 'Wallet',
                'sort' => 9,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 9],
                ],
                'permissions' =>  [
                    [
                        'key' => 'app-wallet',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['transfer-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'transfer-switch'],
                        ],
                    ],
                    [
                        'key' => 'core-wallet-transactions',
                        'except' => ['create'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'edit', 'delete', 'show'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'charge system',
                'sort' => 10,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 10],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 3],
                    PermissionType::AREA_MANAGER->value => ['sort' => 3],
                ],
                'permissions' => [
                    [
                        'key' => 'charger-reports',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],

                    ],
                    [
                        'key' => 'charge-settings',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],
                    ],
                    [
                        'key' => 'coin-recharge',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['add-switch', 'charge-report-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'add-switch', 'charge-report-switch'],
                            PermissionType::SUPER_ADMIN->value => ['browse', 'add-switch'],
                            PermissionType::AREA_MANAGER->value => ['browse', 'add-switch'],
                        ],
                    ],
                    [
                        'key' => 'charge-to-user',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['add-switch', 'history-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'add-switch', 'history-switch'],
                        ],
                    ],
                    [
                        'key' => 'host-diamond',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'users',
                'sort' => 11,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 11],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 2],
                    PermissionType::AREA_MANAGER->value => ['sort' => 5],
                ],
                'permissions' => [
                    ['key' => 'deleted-accounts', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['delete-user-account-switch', 'restore-user-account-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'delete-user-account-switch', 'restore-user-account-switch'],
                    ],],
                    ['key' => 'users', 'except' => [], 'additional' => ['level-switch', 'chang-agency-switch', 'charge-switch', 'invite-switch', 'can-Play-switch', 'kick-family-switch', 'kick-agency-switch', 'salary-switch', 'delete-profile-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete', 'show', 'level-switch', 'chang-agency-switch', 'charge-switch', 'invite-switch', 'can-Play-switch', 'kick-family-switch', 'kick-agency-switch', 'salary-switch', 'delete-profile-switch'],
                        PermissionType::SUPER_ADMIN->value => ['browse', 'show'],
                        PermissionType::AREA_MANAGER->value => ['browse', 'show'],
                    ],],
                    ['key' => 'complaints', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'change-country-request', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['status-switch', 'history-switch', 'all-status-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'status-switch', 'history-switch', 'all-status-switch'],
                    ],],
                    ['key' => 'user-setting', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'user-coin-report', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],

                ],
            ],
            [
                'name' => 'Advertisements',
                'sort' => 12,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 12],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 7],
                    PermissionType::AREA_MANAGER->value => ['sort' => 8],
                ],
                'permissions' => [
                    ['key' => 'banner', 'except' => [], 'additional' => ['action-switch'], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                        PermissionType::SUPER_ADMIN->value => ['browse', 'action-switch', 'create'],
                    ],],
                    ['key' => 'banner-setting', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'splash', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'official-messages', 'except' => ['edit'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['create', 'browse', 'delete', 'show'],
                        PermissionType::SUPER_ADMIN->value => ['create', 'browse', 'delete', 'show'],
                        PermissionType::AREA_MANAGER->value => ['create', 'browse', 'delete', 'show'],
                    ],],
                    ['key' => 'advertising-space', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Store',
                'sort' => 13,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 13],
                ],
                'permissions' => [
                    ['key' => 'store', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Distinguished identifier',
                'sort' => 14,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 14],
                ],
                'permissions' => [
                    ['key' => 'featured-ids', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'details-of-unique-identifiers', 'except' => ['create', 'edit', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['delete', 'browse'],
                    ],],
                    ['key' => 'id-color', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Vip',
                'sort' => 15,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 15],
                ],
                'permissions' => [
                    ['key' => 'VIPs', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'vip-gift', 'except' => ['show',], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'vip-privilege', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'families',
                'sort' => 16,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 16],
                ],
                'permissions' => [
                    ['key' => 'families', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'families-level', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'family-setting', 'except' => ['create', 'edit', 'show', 'delete'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],

            [
                'name' => 'Agency System',
                'sort' => 18,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 18],
                ],
                'permissions' => [
                    ['key' => 'agency-settings', 'except' => ['create', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit'],
                    ],],


                    ['key' => 'templates-form', 'except' => ['create', 'delete',], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit', 'show'],
                    ],],


                    ['key' => 'form-request', 'except' => ['create', 'edit'], 'additional' => ['type-switch', 'approve-switch', 'reject-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'type-switch', 'approve-switch', 'reject-switch', 'show'],
                    ],],
                ],

            ],
            [
                'name' => 'Agency System',
                'sort' => 19,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 19],
                ],
                'permissions' => [

                    ['key' => 'salary', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],

                ],
            ],
            [
                'name' => 'Internal Sales System',
                'sort' => 20,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 20],
                ],
                'permissions' => [
                    ['key' => 'salary-payment-countries', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'salary-requests', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    [
                        'key' => 'internal-sales-system-report',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => ['rejected-request-switch', 'accept-request-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'rejected-request-switch', 'accept-request-switch'],
                        ],
                    ],
                    ['key' => 'transaction-request-problem', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Host Agencies',
                'sort' => 21,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 21],
                ],
                'permissions' => [
                    ['key' => 'reports', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'achieved-Target', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'agencies', 'except' => ['delete'], 'additional' => ['delete-switch', 'change-users-agency-switch', 'kick-switch', 'make-admin-switch', 'remove-admin-switch', 'member-switch', 'charge-history-switch', 'salary-switch', 'join-switch', 'target-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'show', 'delete-switch', 'change-users-agency-switch', 'kick-switch', 'make-admin-switch', 'remove-admin-switch', 'member-switch', 'charge-history-switch', 'salary-switch', 'join-switch', 'target-switch'],
                    ],],
                    ['key' => 'host-agencies-report', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'users-Wallet', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'hosts-target', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'remaining-diamonds-history', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'hosts', 'except' => ['create'], 'additional' => ['charge-switch', 'chang-agency-switch', 'invite-switch', 'can-Play-host-switch', 'kick-agency-host-switch', 'kick-family-host-switch',], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit', 'delete', 'show', 'charge-switch', 'chang-agency-switch', 'invite-switch', 'can-Play-host-switch', 'kick-agency-host-switch', 'kick-family-host-switch'],
                    ],],
                    ['key' => 'target', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],

                ],
            ],
            [
                'name' => 'host level',
                'sort' => 22,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 22],
                ],
                'permissions' => [
                    [
                        'key' => 'host-level',
                        'except' => [],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => $defaultMethods,

                        ],
                    ],
                    [
                        'key' => 'host-level-settings',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],

                        ],
                    ],
                    [
                        'key' => 'host-level-reward',
                        'except' => [],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => $defaultMethods,

                        ],
                    ],
                ],
            ],
            [
                'name' => 'Agency Settings',
                'sort' => 23,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 23],
                ],
                'permissions' => [
                    ['key' => 'agencies-join-requests', 'except' => ['create'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit', 'delete', 'show'],
                    ],],

                    ['key' => 'request-agencies', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['accept-agency', 'refuse-agency'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'accept-agency', 'refuse-agency'],
                    ],],
                    ['key' => 'request-agency-history', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Charging Agencies',
                'sort' => 24,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 24],
                ],
                'permissions' => [
                    ['key' => 'appear-charger-agency', 'except' => ['delete', 'show'], 'additional' => ['delete-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete-switch'],
                    ],],
                    ['key' => 'charge-agency', 'except' => [], 'additional' => ['actions-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete', 'show', 'actions-switch'],
                    ],],
                    ['key' => 'payment-gat-way', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'agency-manger-setting', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'Payment-methods-for-shipping-agencies', 'except' => ['browse', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['create', 'edit', 'delete'],
                    ],],
                    ['key' => 'salary-payment-countries', 'except' => ['show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete'],
                    ],],
                ],
            ],
            [
                'name' => 'Agency Manager',
                'sort' => 25,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 25],
                ],
                'permissions' => [
                    [
                        'key' => 'BD',
                        'except' => [],
                        'additional' => ['delete-switch', 'choose-switch', 'stop-salary-switch'],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse', 'delete-switch', 'choose-switch', 'stop-salary-switch', 'create', 'edit', 'delete', 'show'],
                        ],
                    ],

                ],
            ],
            [
                'name' => 'Room',
                'sort' => 26,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 26],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 6],
                    PermissionType::AREA_MANAGER->value => ['sort' => 7],
                ],
                'permissions' => [
                    ['key' => 'rooms', 'except' => ['create'], 'additional' => ['actions-switch', 'pin-switch', 'close-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'show', 'edit', 'delete', 'actions-switch', 'pin-switch', 'close-switch'],
                        PermissionType::SUPER_ADMIN->value => ['browse', 'show'],
                        PermissionType::AREA_MANAGER->value => ['browse', 'show'],
                    ],],
                    ['key' => 'live-rooms', 'except' => ['create'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'show', 'edit', 'delete',],
                        PermissionType::SUPER_ADMIN->value => ['browse', 'show'],
                        PermissionType::AREA_MANAGER->value => ['browse', 'show'],
                    ],],
                    ['key' => 'categories', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'room-vip', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'room-background', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],

                    ['key' => 'charisma-levels', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],

                    ['key' => 'room-settings', 'except' => ['create', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit'],
                    ],],
                    ['key' => 'room-level-history', 'except' => ['edit', 'delete', 'show', 'create'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'room-cup-target',
                'sort' => 27,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 27],
                ],
                'permissions' => [

                    ['key' => 'room-cup-target', 'except' => [], 'additional' => ['move-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['move-switch', 'browse', 'create', 'delete', 'show', 'edit'],
                    ],],
                    ['key' => 'room-cup-settings', 'except' => ['create', 'delete', 'show', 'edit'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'room-cup-report', 'except' => ['create', 'delete', 'show', 'edit'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Gift',
                'sort' => 27,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 27],
                ],
                'permissions' => [

                    ['key' => 'gift', 'except' => [], 'additional' => ['move-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['move-switch', 'browse', 'create', 'delete', 'show', 'edit'],
                    ],],
                    ['key' => 'gift-categories', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'gift-logs', 'except' => ['create', 'delete', 'show', 'edit'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                    ['key' => 'lucky-gift-setting', 'except' => ['create', 'delete', 'show', 'edit'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],

            [
                'name' => 'emojis',
                'sort' => 28,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 28],
                ],
                'permissions' => [

                    ['key' => 'emoji', 'except' => [], 'additional' => ['move-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['move-switch', 'browse', 'create', 'delete', 'show', 'edit'],
                    ],],
                    ['key' => 'emoji-categories', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Cp',
                'sort' => 29,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 29],
                ],
                'permissions' => [

                    ['key' => 'cp-report', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['cancel-cp-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'cancel-cp-switch'],
                    ],],
                    ['key' => 'cp-relation', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'weekly-cp', 'except' => ['show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create', 'edit', 'delete',],
                    ],],
                    ['key' => 'cp-setting', 'except' => ['show', 'create', 'edit', 'delete'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Custom achievement',
                'sort' => 30,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 30],
                ],
                'permissions' => [

                    ['key' => 'custom-achievement', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],

                ],
            ],
            [
                'name' => 'Achievements',
                'sort' => 31,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 31],
                ],
                'permissions' => [
                    ['key' => 'achievement', 'except' => ['create', 'delete'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit',],
                    ],],
                    ['key' => 'achievement_level', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'user_achievement_level', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Invitation Code',
                'sort' => 32,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 32],
                ],
                'permissions' => [
                    ['key' => 'user-parent', 'except' => ['create', 'delete', 'edit', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse',],
                    ],],
                    ['key' => 'invitation-code-setting', 'except' => ['create', 'delete', 'edit', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse',],
                    ],],
                    ['key' => 'invitation-code', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse',],
                    ],],
                ],
            ],
            [
                'name' => 'Group chat',
                'sort' => 33,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 33],
                ],
                'permissions' => [
                    ['key' => 'group-chat', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'updates_group_chat', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Lucky box',
                'sort' => 34,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 34],
                ],
                'permissions' => [
                    ['key' => 'boxes', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'box-use', 'except' => ['create'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit', 'delete', 'show'],
                    ],],
                    ['key' => 'box-settings', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Events',
                'sort' => 35,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 35],
                ],
                'permissions' => [
                    ['key' => 'event-period', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'weekly-star', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'weekly_star_rewards', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'target-event', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'charge-king', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'gift-target-event', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'pk-event', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'pk-event-rewards', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'general-roles', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'event_report', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['return-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'return-switch'],
                    ],],
                ],
            ],
            [
                'name' => 'Milestone',
                'sort' => 36,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 36],

                ],
                'permissions' => [
                    ['key' => 'milestone', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'dedicate-switch'],

                    ],],

                ],
            ],
            [
                'name' => 'Reels',
                'sort' => 37,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 37],
                ],
                'permissions' => [
                    ['key' => 'Real', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'report-real', 'except' => ['create', 'edit', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'delete'],
                    ],],
                ],
            ],
            [
                'name' => 'Moment',
                'sort' => 38,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 38],
                ],
                'permissions' => [
                    ['key' => 'Moment', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'report-moment', 'except' => ['edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'create'],
                    ],],
                ],
            ],
            [
                'name' => 'Employees and Permissions',
                'sort' => 39,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 39],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 9],
                    PermissionType::AREA_MANAGER->value => ['sort' => 9],
                ],
                'permissions' => [
                    ['key' => 'auth-users', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                        PermissionType::SUPER_ADMIN->value => $defaultMethods,
                        PermissionType::AREA_MANAGER->value => $defaultMethods,
                    ],],
                    ['key' => 'roles', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                        PermissionType::SUPER_ADMIN->value => $defaultMethods,
                        PermissionType::AREA_MANAGER->value => $defaultMethods,
                    ],],
                    ['key' => 'roles-reward', 'except' => ['show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Work Settings',
                'sort' => 40,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 40],
                ],
                'permissions' => [
                    ['key' => 'delete-account-details', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'questions', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'country', 'except' => ['show', 'create'], 'additional' => ['status-switch'], 'types' => [
                        PermissionType::ADMIN->value => ['status-switch', 'edit', 'browse'],
                    ],],
                    ['key' => 'page', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'payment-coin', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'coins', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'exchange', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'gold-coins', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Sensitive Settings',
                'sort' => 41,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 41],
                ],
                'permissions' => [
                    ['key' => 'updates', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'config', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'pusher-statistics', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'System Settings',
                'sort' => 42,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 42],
                ],
                'permissions' => [
                    ['key' => 'settings', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'language', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'daily-prize', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'daily-gift', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Level',
                'sort' => 43,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 43],
                ],
                'permissions' => [
                    ['key' => 'level', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'level-interval', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'reward-level-interval', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],

                ],
            ],
            [
                'name' => 'tribe events',
                'sort' => 44,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 44],
                ],
                'permissions' => [
                    ['key' => 'tribe-periods', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'tribe-tops', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'tribe-rewards', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'Room Boom',
                'sort' => 45,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 45],
                ],
                'permissions' => [
                    ['key' => 'room-boom-levels', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'room-boom-rewards', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'super-boom-rules', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    [
                        'key' => 'room-boom-winners',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],
                    ],
                    [
                        'key' => 'room-boom-settings',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'reward',
                'sort' => 46,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 46],
                ],
                'permissions' => [
                    [
                        'key' => 'user-reward',
                        'except' => ['create', 'edit', 'delete', 'show'],
                        'additional' => [],
                        'types' => [
                            PermissionType::ADMIN->value => ['browse'],

                        ],
                    ],

                ],
            ],

            [
                'name' => 'Bds',
                'sort' => 38,
                'types' => [
                    PermissionType::AREA_MANAGER->value => ['sort' => 4],
                    PermissionType::SUPER_ADMIN->value => ['sort' => 3],
                ],
                'permissions' => [
                    [
                        'key' => 'Bds',
                        'except' => ['delete'],
                        'additional' => ['delete-switch', 'choose-switch', 'stop-salary-switch'],
                        'types' => [
                            PermissionType::SUPER_ADMIN->value => ['browse', 'delete-switch', 'choose-switch', 'stop-salary-switch', 'create', 'edit',  'show'],
                            PermissionType::AREA_MANAGER->value => ['browse', 'delete-switch', 'choose-switch', 'stop-salary-switch', 'create', 'edit',  'show'],


                        ],
                    ],
                    [
                        'key' => 'professional-bd',
                        'except' => ['delete'],
                        'create',
                        'edit',
                        'additional' => [],
                        'types' => [
                            PermissionType::SUPER_ADMIN->value => ['browse', 'show'],
                            PermissionType::AREA_MANAGER->value => ['browse', 'show'],

                        ],
                    ],

                ],
            ],
            [
                'name' => 'Agencies',
                'sort' => 39,
                'types' => [

                    PermissionType::SUPER_ADMIN->value => ['sort' => 5],
                    PermissionType::AREA_MANAGER->value => ['sort' => 6],
                ],
                'permissions' => [


                    ['key' => 'agency', 'except' => ['delete'], 'additional' => ['delete-switch', 'change-users-agency-switch',], 'types' => [

                        PermissionType::SUPER_ADMIN->value => ['browse', 'delete-switch', 'change-users-agency-switch', 'show', 'create', 'edit',],
                        PermissionType::AREA_MANAGER->value => ['browse', 'delete-switch', 'change-users-agency-switch', 'show', 'create', 'edit',],

                    ],],
                    ['key' => 'shipping-agency', 'except' => ['delete', 'show'], 'additional' => ['delete-switch', 'switches-switch'], 'types' => [

                        PermissionType::SUPER_ADMIN->value => ['browse', 'create', 'edit', 'delete-switch'],
                        PermissionType::AREA_MANAGER->value => ['browse', 'create', 'edit', 'delete-switch'],

                    ],],

                    ['key' => 'professional-users', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [

                        PermissionType::SUPER_ADMIN->value => ['browse'],
                        PermissionType::AREA_MANAGER->value => ['browse'],
                    ],],
                    ['key' => 'host', 'except' => ['create'], 'additional' => [], 'types' => [
                        PermissionType::SUPER_ADMIN->value => ['browse', 'edit', 'delete', 'show',],
                        PermissionType::AREA_MANAGER->value => ['browse', 'edit',  'show',],
                    ],],

                ],
            ],
            [
                'name' => 'super rewards',
                'sort' => 40,
                'types' => [

                    PermissionType::SUPER_ADMIN->value => ['sort' => 8],
                    PermissionType::AREA_MANAGER->value => ['sort' => 8],
                ],
                'permissions' => [

                    ['key' => 'reward-center', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => ['dedicate-switch'], 'types' => [
                        PermissionType::AREA_MANAGER->value => ['browse', 'dedicate-switch'],
                        PermissionType::SUPER_ADMIN->value => ['browse', 'dedicate-switch'],

                    ],],

                ],
            ],
            [
                'name' => 'wallet fields',
                'sort' => 47,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 47],
                ],
                'permissions' => [
                    ['key' => 'wallet-template', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    ['key' => 'wallet-fields', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
            [
                'name' => 'ranking rewards',
                'sort' => 48,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 48],
                ],
                'permissions' => [
                    ['key' => 'ranking-types', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],

            [
                'name' => 'addons',
                'sort' => 49,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 49],
                ],
                'permissions' => [
                    ['key' => 'app-feature', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Default App Screen Settings',
                'sort' => 50,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 50],
                ],
                'permissions' => [
                    ['key' => 'default-screen-settings', 'except' => ['create', 'edit', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse'],
                    ],],
                ],
            ],
            [
                'name' => 'Notifications',
                'sort' => 52,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 52],
                ],
                'permissions' => [
                    // Templates page (NotificationsTemplatesController,
                    // permission_name = 'notification').
                    ['key' => 'notification', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                    // Aggregated settings page (NotificationSettingsController).
                    ['key' => 'notification-setting', 'except' => ['create', 'delete', 'show'], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => ['browse', 'edit'],
                    ],],
                ],
            ],
            [
                'name' => 'Sensitive Word',
                'sort' => 51,
                'types' => [
                    PermissionType::ADMIN->value => ['sort' => 51],
                ],
                'permissions' => [
                    ['key' => 'sensitive-word', 'except' => [], 'additional' => [], 'types' => [
                        PermissionType::ADMIN->value => $defaultMethods,
                    ],],
                ],
            ],
        ];



        $allSlugs = [];

        foreach ($categories as $category) {

            // ✅ Ensure 'types' exists
            if (!isset($category['types'])) continue;

            foreach ($category['types'] as $type => $typeData) {
                $categoryName = $category['name'];
                $categorySlug = $categoryName;

                DB::table('role_categories')->updateOrInsert(
                    ['slug' => $categorySlug, 'type' => $type],
                    [
                        'name_en' => $categoryName,
                        'sort' => $typeData['sort'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $categoryId = DB::table('role_categories')
                    ->where('slug', $categorySlug)
                    ->where('type', $type)
                    ->value('id');

                foreach ($category['permissions'] as $perm) {
                    if (!isset($perm['types'])) continue;

                    foreach ($perm['types'] as $permType => $methods) {
                        if ($permType != $type) continue;

                        foreach ($methods as $method) {
                            if (in_array($method, $perm['except'] ?? [])) continue;

                            $slug = "{$method}-{$perm['key']}";
                            $name = ucfirst($method) . ' ' . str_replace('-', ' ', $perm['key']);

                            // 🔹 Save category as slug
                            DB::table('admin_permissions')->updateOrInsert(
                                ['slug' => $slug],
                                [
                                    'name' => $name,
                                    'category' => $categorySlug,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );

                            $permissionId = DB::table('admin_permissions')
                                ->where('slug', $slug)
                                ->value('id');

                            $allSlugs[] = $slug;

                            DB::table('permission_types')->updateOrInsert(
                                ['permission_id' => $permissionId, 'type' => $type],
                                [
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }

                        foreach ($perm['additional'] as $extra) {

                            // Only add this extra if it exists in the current $permType list
                            if (!in_array($extra, $perm['types'][$type] ?? [])) {
                                continue; // skip if this type doesn't have this additional permission
                            }

                            $slug = "{$extra}-{$perm['key']}";
                            $name = ucfirst(str_replace('-', ' ', $slug));

                            DB::table('admin_permissions')->updateOrInsert(
                                ['slug' => $slug],
                                [
                                    'name' => $name,
                                    'category' => $categorySlug,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );

                            $permissionId = DB::table('admin_permissions')
                                ->where('slug', $slug)
                                ->value('id');

                            $allSlugs[] = $slug;

                            DB::table('permission_types')->updateOrInsert(
                                ['permission_id' => $permissionId, 'type' => $type],
                                [
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }
        }
        $lastSort = RoleCategory::where('type', PermissionType::ADMIN->value)->max('sort');
        RoleCategory::updateOrCreate(
            ['name_en' => 'general'],
            ['slug' => 'general', 'sort' => $lastSort + 1]
        );
        DB::table('admin_permissions')
            ->whereNull('category')
            ->update(['category' => 'general']);


        // Re-assert the wildcard BEFORE the cleanup below: if the '*' row was
        // ever renamed or removed, the upsert restores it first, so the
        // slug-based guard on the delete always has a real row to protect.
        $this->ensureWildcardPermission();

        DB::table('admin_permissions')
            ->whereNotIn('slug', $allSlugs)
            ->where('slug', '!=', '*')
            ->delete();

        $role = DB::table('admin_roles')->where('slug', 'region')->first();
        if ($role) {
            $permissions = DB::table('admin_permissions')
                ->where('slug', 'like', '%reward-center%')
                ->get();

            foreach ($permissions as $permissionId) {
                DB::table('admin_role_permissions')->updateOrInsert(
                    ['role_id' =>  $role->id, 'permission_id' => $permissionId->id]
                );
            }
        }

        $this->flushCachedAdminPermissions();
    }

    /**
     * The '*' wildcard row is the super-admin bypass the whole panel depends on
     * (Encore Permission middleware + AdminRbacGuard::isSuper). The cleanup above
     * only spares it by slug, so if the row was ever renamed/removed it stays
     * gone and every admin is locked out of the dashboard. Re-assert it and its
     * links to the canonical super roles on every run — idempotent.
     */
    private function ensureWildcardPermission(): void
    {
        DB::table('admin_permissions')->updateOrInsert(
            ['slug' => '*'],
            [
                'name' => 'All permission',
                'http_method' => '',
                'http_path' => '*',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('admin_permissions')->where('slug', '*')->value('id');

        $superRoleIds = DB::table('admin_roles')
            ->whereIn('slug', ['developer', 'admin', 'administrator'])
            ->pluck('id');

        foreach ($superRoleIds as $roleId) {
            DB::table('admin_role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * App\Models\Admin caches allPermissions() per admin (versioned keys, see
     * HasPermissions) — a permission reshuffle here would otherwise be
     * invisible (or, worse, an empty set cached during the run would stick
     * around and lock the panel). Bump the global version + drop legacy keys.
     */
    private function flushCachedAdminPermissions(): void
    {
        \App\Models\Admin::flushAllCachedPermissions();

        foreach (DB::table('admin_users')->pluck('id') as $id) {
            \Cache::forget("user_permissions_{$id}");
            \Cache::forget("admin_user_permissions_{$id}");
        }
    }
}
