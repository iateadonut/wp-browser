<?php

namespace lucatume\WPBrowser\Command;

use lucatume\WPBrowser\Lib\Generator\WPUnit;
use lucatume\WPBrowser\TestCase\WPRestControllerTestCase;

class GenerateWPRestController extends GenerateWPUnit
{
    /**
     * Returns the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return 'Generates a WPRestApiTestCase: a WP_Test_REST_Controller_Testcase '
            . 'with Codeception super-powers.';
    }

    /**
     * Returns the test case generator.
     *
     * @param array{namespace: string, actor: string} $config The generator configuration.
     * @param string $class The class to generate the test case for.
     *
     * @return WPUnit The generator instance.
     */
    protected function getGenerator(array $config, string $class): WPUnit
    {
        return new WPUnit($config, $class, WPRestControllerTestCase::class);
    }

    /**
     * Returns the name of the command.
     *
     * @return string The command name.
     */
    public static function getCommandName(): string
    {
        return 'generate:wprestcontroller';
    }
}
