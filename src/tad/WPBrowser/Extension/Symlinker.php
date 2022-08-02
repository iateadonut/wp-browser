<?php
/**
 * Symlinks files and folders into specified destinations before tests.
 *
 * @package tad\WPBrowser\Extension
 */

namespace tad\WPBrowser\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Events as CodeceptionEvents;
use Codeception\Exception\ExtensionException;
use Codeception\Extension;
use Codeception\Lib\Console\Output;
use Exception;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class Symlinker
 *
 * @package tad\WPBrowser\Extension
 */
class Symlinker extends Extension
{
    /**
     * A list of events the extension hooks on.
     *
     * @var array<string>
     */
    public static array $events = [
        CodeceptionEvents::MODULE_INIT => 'symlink',
        CodeceptionEvents::SUITE_AFTER => 'unlink',
    ];

    /**
     * A list of the required configuration settings.
     *
     * @var array<int|string,array<int,string>|string>
     */
    protected array $required = ['mode' => ['plugin', 'theme'], 'destination'];

    /**
     * Symlinker constructor.
     *
     * @param array<string,mixed> $config The configuration contents.
     * @param array<string,mixed> $options An array of options..
     */
    public function __construct($config, $options)
    {
        parent::__construct($config, $options);
    }

    /**
     * Symbolically links one or more source folders to one or more destinations.
     *
     * @param SuiteEvent $event The event the method is running on.
     *
     * @throws ExtensionException If there are issues with the source or destination folders.
     *
     * @return void
     */
    public function symlink(SuiteEvent $event): void
    {
        $eventSettings =(array)$event->getSettings();
        $rootFolder = $this->getRootFolder($eventSettings);
        $destination = $this->getDestination($rootFolder, $eventSettings);

        try {
            if (!file_exists($destination)) {
                if (!symlink($rootFolder, $destination)) {
                    throw new ExtensionException(
                        $this,
                        "Symbolic linking {$rootFolder} -> {$destination} failed; " .
                        "it will never succeed on Windows, use the Copier extension."
                    );
                }
                $this->writeln('Symbolically linked plugin folder [' . $destination . ']');
            }
        } catch (IOException $event) {
            throw new ExtensionException(
                __CLASS__,
                "Error while trying to symlink plugin or theme to destination.\n\n" . $event->getMessage()
            );
        }
    }

    /**
     * Returns the root folder for a symlink.
     *
     * @param array<string,mixed> $settings The current settings.
     *
     * @return string The root folder path.
     */
    protected function getRootFolder(array $settings = []): string
    {
        $rootFolder = $this->config['rootFolder'] ?? rtrim(codecept_root_dir(), DIRECTORY_SEPARATOR);

        if (is_array($rootFolder)) {
            $rootFolder = $this->getDirPathFromArrayDefinition($settings, $rootFolder);
        }

        return $rootFolder;
    }

    /**
     * Returns the symlink destination folder.
     *
     * @param string $rootFolder The root folder path.
     * @param array<string,mixed> $settings The current extension settings.
     *
     * @return string The destination path or an array of destination paths.
     */
    protected function getDestination(string $rootFolder, array $settings = []): string
    {
        $destination = $this->config['destination'];

        if (is_array($destination)) {
            $destination = $this->getDirPathFromArrayDefinition($settings, $destination);
        }

        return rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($rootFolder);
    }

    /**
     * Unlinks the plugin(s) symbolically linked by the extension.
     *
     * @param SuiteEvent $event The suite event the operation is hooking on.
     */
    public function unlink(SuiteEvent $event): void
    {
        $eventSettings =(array)$event->getSettings();
        $rootFolder = $this->getRootFolder($eventSettings);
        $destination = $this->getDestination($rootFolder, $eventSettings);

        if (is_file($destination)) {
            try {
                if (!(unlink($destination))) {
                    // Let's not kill the suite but let's notify the user.
                    $this->writeln(
                        sprintf(
                            'Could not unlink file [%s], manual removal is required.',
                            $destination
                        )
                    );
                }
            } catch (Exception $event) {
                // Let's not kill the suite but let's notify the user.
                $this->writeln(sprintf(
                    "There was an error while trying to unlink file [%s], manual removal is required.\nError: %s",
                    $destination,
                    $event->getMessage()
                ));
                return;
            }

            $this->writeln('Unliked plugin folder [' . $destination . ']');
        }
    }

    /**
     * Initializes the extension, checking its settings.
     *
     * @throws ExtensionException If the settings are not valid.
     *
     * @return void
     */
    public function _initialize(): void
    {
        parent::_initialize();
        $this->checkRequirements();
    }

    /**
     * Checks that the extension broader requirements are met.
     *
     * @return void
     *
     * @throws ExtensionException If one or more requirements are not satisfied.
     */
    protected function checkRequirements(): void
    {
        if (!isset($this->config['mode'])) {
            throw new ExtensionException(__CLASS__, 'Required configuration parameter [mode] is missing.');
        }
        if (!array_intersect((array)$this->required['mode'], (array)$this->config['mode'])) {
            throw new ExtensionException(
                __CLASS__,
                '[mode] should be one among these values: [' . implode(', ', (array)$this->required['mode']) . ']'
            );
        }
        if (!isset($this->config['destination'])) {
            throw new ExtensionException(__CLASS__, 'Required configuration parameter [destination] is missing.');
        }

        $destination = (array)$this->config['destination'];

        array_walk($destination, [$this, 'checkDestination']);

        if (isset($this->config['rootFolder'])) {
            $rootFolder = (array)$this->config['rootFolder'];
            array_walk($rootFolder, [$this, 'checkRootFolder']);
        }
    }

    /**
     * Checks the destination folder to make sure it exists and is writable.
     *
     * @param string $destination The path to the destination folder.
     *
     * @return void
     *@throws ExtensionException If the destination folder does not exist or is not writable.
     *
     */
    protected function checkDestination(string $destination): void
    {
        if (!(is_dir($destination) && is_writable($destination))) {
            throw new ExtensionException(
                __CLASS__,
                '[destination] parameter [' . $destination . '] is not an existing and writeable directory.'
            );
        }
    }

    /**
     * Checks the root folder specified in the settings to make sure it exists and it's writeable.
     *
     * @param string $rootFolder The path to the root folder.
     *
     * @return void
     *@throws ExtensionException If the root folder does not exist or is not readable.
     *
     */
    protected function checkRootFolder(string $rootFolder): void
    {
        if (!(is_dir($rootFolder) && is_readable($rootFolder))) {
            throw new ExtensionException(
                __CLASS__,
                '[rootFolder] parameter [' . $rootFolder . '] is not an existing and readable directory.'
            );
        }
    }

    /**
     * Parses and returns the environments, if any, from the settings.
     *
     * @param array<string,mixed> $settings The settings array.
     *
     * @return array<int,string>|false. The environment(s) found in the settings, or `default` if none was found.
     */
    protected function getCurrentEnvsFromSettings(array $settings): array|false
    {
        $rawCurrentEnvs = empty($settings['current_environment']) ? 'default' : $settings['current_environment'];

        return preg_split('/\\s*,\\s*/', $rawCurrentEnvs);
    }

    /**
     * Sets the output the instance should use.
     *
     * @param Output $output The output the instance should use.
     */
    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    private function getDirPathFromArrayDefinition(array $settings, array $dirDefinition): string
    {
        $currentEnvs = $this->getCurrentEnvsFromSettings($settings);
        $fallbackRootFolder = $dirDefinition['default'] ?? reset($dirDefinition);
        $supportedEnvs = array_intersect(array_keys($dirDefinition), (array)$currentEnvs);
        $firstSupported = reset($supportedEnvs);

        return $dirDefinition[$firstSupported] ?? $fallbackRootFolder;
    }
}
