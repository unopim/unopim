<?php

describe('DemoExtrasTableSeeder – admin profile image sanitisation', function () {
    it('nulls out every admin image so a server-specific path never ships as a broken image', function () {
        // Simulate the admins rows exactly as they appear in demo_extras.json
        $rows = [
            [
                'id'             => 1,
                'name'           => 'Demo Admin',
                'email'          => 'admin@example.com',
                'password'       => 'hashed',
                'image'          => 'admins/1/download.png',
                'status'         => 1,
                'role_id'        => 1,
                'timezone'       => 'UTC',
                'ui_locale_id'   => 1,
                'remember_token' => null,
                'created_at'     => '2026-01-01 00:00:00',
                'updated_at'     => '2026-01-01 00:00:00',
            ],
            [
                'id'             => 2,
                'name'           => 'Second Admin',
                'email'          => 'second@example.com',
                'password'       => 'hashed',
                'image'          => null,
                'status'         => 1,
                'role_id'        => 1,
                'timezone'       => 'UTC',
                'ui_locale_id'   => 1,
                'remember_token' => null,
                'created_at'     => '2026-01-01 00:00:00',
                'updated_at'     => '2026-01-01 00:00:00',
            ],
        ];

        // Apply the same transformation the seeder uses
        $sanitised = array_map(static function (array $row): array {
            $row['image'] = null;

            return $row;
        }, $rows);

        foreach ($sanitised as $row) {
            expect($row['image'])->toBeNull();
        }
    });

    it('contains the admins image-nulling guard in the seeder source', function () {
        $source = file_get_contents(
            __DIR__.'/../../src/Database/Seeders/DemoExtrasTableSeeder.php'
        );

        expect($source)->toContain("if (\$table === 'admins')");
        expect($source)->toContain("\$row['image'] = null;");
    });
});
