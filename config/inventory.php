<?php

return [
    /*
     * Seed data for the ByteStock computer parts shop.
     * Categories and brands become first-class records in the database;
     * these lists keep the seeders and product fixtures in sync.
     */
    'categories' => [
        'CPU',
        'Graphics Card',
        'Motherboard',
        'Memory (RAM)',
        'Storage',
        'Power Supply',
        'PC Case',
        'Cooling',
        'Peripheral',
        'Networking',
    ],

    'brands' => [
        'AMD',
        'Intel',
        'NVIDIA',
        'ASUS',
        'MSI',
        'Gigabyte',
        'Corsair',
        'G.Skill',
        'Samsung',
        'WD',
        'Seagate',
        'be quiet!',
        'NZXT',
        'Lian Li',
        'Noctua',
        'Arctic',
        'Logitech',
        'Keychron',
        'TP-Link',
    ],
];
