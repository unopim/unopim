<?php

it('should persist an empty value when a core-config field is submitted as null (Issue #718)', function () {
    $source = file_get_contents(__DIR__.'/../../src/Repositories/CoreConfigRepository.php');

    expect($source)->toContain("\$value = '';");
    expect($source)->not->toContain("} else {\n                        continue;\n                    }\n                }\n\n                if (\$field['type']");
});
