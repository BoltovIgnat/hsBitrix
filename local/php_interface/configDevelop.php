<?php

return [
    'sites' => [
        'dev.highsystem.ru:443' => [
            'SITE_URL' => 'dev.highsystem.ru:443',
            'ALLOW_GROUP' => 47,
            'FIELDS' => [
                'LEAD' => [
                    'UF_FIELD_1' => 101,
                    'UF_FIELD_2' => 102,
                ],
                'DEAL' => [
                    'UF_FIELD_1' => 201,
                    'UF_FIELD_2' => 202,
                ],
                'COMPANY' => [
                    'UF_FIELD_1' => 301,
                    'UF_FIELD_2' => 302,
                ],
            ],
            'ALLOW_IP' => [
                '192.168.25.*',     // Офис
                '192.168.30.*',     // Склад
                '192.168.40.*',     // Вологда
                '192.168.80.*',     // VPN
                '95.64.198.250',    // Офис внешний
                '88.86.72.142',     // Вологда внешний
            ],
        ],
        'crm.highsystem.ru:443' => [
            'SITE_URL' => 'crm.highsystem.ru:443',
            'ALLOW_GROUP' => 50,
            'FIELDS' => [
                'LEAD' => [
                    'UF_FIELD_1' => 101,
                    'UF_FIELD_2' => 102,
                ],
                'DEAL' => [
                    'UF_FIELD_1' => 201,
                    'UF_FIELD_2' => 202,
                ],
                'COMPANY' => [
                    'UF_FIELD_1' => 301,
                    'UF_FIELD_2' => 302,
                ],
            ],
            'ALLOW_IP' => [
                '192.168.25.*',     // Офис
                '192.168.30.*',     // Склад
                '192.168.40.*',     // Вологда
                '192.168.80.*',     // VPN
                '95.64.198.250',    // Офис внешний
                '88.86.72.142',     // Вологда внешний
            ],
        ],
        'crm.highsystem.ru:80' => [
            'SITE_URL' => 'crm.highsystem.ru:80',
            'ALLOW_GROUP' => 50,
            'FIELDS' => [
                'LEAD' => [
                    'UF_FIELD_1' => 101,
                    'UF_FIELD_2' => 102,
                ],
                'DEAL' => [
                    'UF_FIELD_1' => 201,
                    'UF_FIELD_2' => 202,
                ],
                'COMPANY' => [
                    'UF_FIELD_1' => 301,
                    'UF_FIELD_2' => 302,
                ],
            ],
            'ALLOW_IP' => [
                '192.168.25.*',     // Офис
                '192.168.30.*',     // Склад
                '192.168.40.*',     // Вологда
                '192.168.80.*',     // VPN
                '95.64.198.250',    // Офис внешний
                '88.86.72.142',     // Вологда внешний
            ],
        ],
    ],
];