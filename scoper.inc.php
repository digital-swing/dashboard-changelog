<?php

declare(strict_types=1);

use Sematico\Scoper\Configurator;

require_once __DIR__ . '/tools/wp-phpscoper/vendor/autoload.php';

$configurator = new Configurator('DC');

return [
    // Préfixe unique à ton plugin
    'prefix'                  => $configurator->getPrefix(),

    'exclude-classes'         =>  array_merge(
        $configurator->getAllGeneratedClassesExclusions(),
        ['Composer\Autoload\ClassLoader']
    ),
    'exclude-functions'       => $configurator->getAllGeneratedFunctionsExclusions(),
    'exclude-constants'       => $configurator->getAllGeneratedConstantsExclusions(),
    'exclude-namespaces'      => array_merge(
        $configurator->getAllGeneratedInterfacesExclusions(),
        ["AC\ListScreenRepository"]
    ),
    // 'patchers'                => array_merge( $configurator->getDefaultPatchers(), $configurator->getPatchers() ),

    'patchers'                => [
        static function (string $filePath, string $prefix, string $content): string {
            if ($filePath === realpath('engine/Initialize.php')) {
                $original_string =  "\$namespacetofind = 'Strada_Events\\\\' . \$namespacetofind;";
                $pattern = "/" . preg_quote($original_string, '/')  . "/";
                $replacement = "\$namespacetofind = '{$prefix}\\\\\\\\Strada_Events\\\\\\\\' . \$namespacetofind;";
                return preg_replace(
                    $pattern,
                    $replacement,
                    $content
                );
            }

            return $content;
        },
        static function (string $filePath, string $prefix, string $content): string {
            if ($filePath === realpath('engine/Initialize.php')) {
                $original_string =  "if (isset(\$classmap['Strada_Events\Engine\Initialize'])) {";
                $pattern = "/" . preg_quote($original_string, '/')  . "/";
                $replacement = "if (isset(\$classmap['{$prefix}\\\\Strada_Events\Engine\Initialize'])) {";
                return preg_replace(
                    $pattern,
                    $replacement,
                    $content
                );
            }

            return $content;
        },
        static function (string $filePath, string $prefix, string $content): string {
            // Corrige les formats DateTime où Scoper a préfixé des séquences de format
            return preg_replace(
                // Cherche des séquences type "DC\Ymd\THis\Z" ou "DC\Ymd\THis"
                sprintf('/%s\\\\(Ymd\\\\THis(?:\\\\Z)?)/', preg_quote($prefix, '/')),
                '$1',
                $content
            );
        },
    ]

];
