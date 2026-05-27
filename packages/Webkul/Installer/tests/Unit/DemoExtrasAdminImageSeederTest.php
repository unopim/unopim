<?php

describe('DemoExtrasTableSeeder – admins table handling', function () {
    it('skips the admins table entirely so the seeder cannot clobber the credentials set via the installer admin step', function () {
        $source = file_get_contents(
            __DIR__.'/../../src/Database/Seeders/DemoExtrasTableSeeder.php'
        );

        expect($source)->toContain("if (\$table === 'admins')");
        expect($source)->toContain('continue;');
        expect($source)->not->toContain("\$row['image'] = null;");
    });
});
