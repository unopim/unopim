<?php

use Webkul\Core\Tree;

function menuItem(string $key, string $url): array
{
    return ['key' => $key, 'url' => $url, 'children' => []];
}

describe('Tree::getActive — path-boundary matching', function () {
    it('does not mark a sibling whose url is a prefix of the current url', function () {
        $tree = new Tree;
        $tree->current = 'http://localhost/admin/configuration/system-information';

        $systemInformation = menuItem('configuration.system_information', 'http://localhost/admin/configuration/system-information');
        $systemSettings = menuItem('configuration.system_settings', 'http://localhost/admin/configuration/system');

        expect($tree->getActive($systemInformation))->toBeTrue();
        expect($tree->getActive($systemSettings))->toBeNull();
    });

    it('marks a menu item active on its exact url', function () {
        $tree = new Tree;
        $tree->current = 'http://localhost/admin/configuration/system';

        expect($tree->getActive(menuItem('configuration.system_settings', 'http://localhost/admin/configuration/system')))->toBeTrue();
    });

    it('marks a parent active when a nested child page is open', function () {
        $tree = new Tree;
        $tree->current = 'http://localhost/admin/configuration/system/theme';

        expect($tree->getActive(menuItem('configuration.system_settings', 'http://localhost/admin/configuration/system')))->toBeTrue();
    });

    it('ignores trailing slashes on either side', function () {
        $tree = new Tree;
        $tree->current = 'http://localhost/admin/configuration/system/';

        expect($tree->getActive(menuItem('configuration.system_settings', 'http://localhost/admin/configuration/system')))->toBeTrue();
    });
});

describe('Tree::getActive — key-hierarchy matching', function () {
    it('marks an ancestor active via the current key but not a prefix-sharing sibling', function () {
        $tree = new Tree;
        $tree->current = 'http://localhost/admin/no-url-match';
        $tree->currentKey = 'configuration.system_information';

        expect($tree->getActive(menuItem('configuration', 'http://localhost/admin/configuration')))->toBeTrue();
        expect($tree->getActive(menuItem('configuration.system_information', 'http://localhost/admin/x')))->toBeTrue();
        expect($tree->getActive(menuItem('configuration.system_settings', 'http://localhost/admin/y')))->toBeNull();
    });
});
