<?php

declare(strict_types=1);

namespace Tests\Support;

use CaptainHook\App\Hook\Condition\Branch\Files;
use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class MyConfigExtension extends \Codeception\Extension
{
    public const NOT_RUNNING_SERVER_DESC = 'Not Running Server';
    public const RUNNING_SERVER_DESC = 'Running Server';

    /**
     * @var array<mixed>
     */
    public static array $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::SUITE_AFTER => 'afterSuite'
    ];
    protected ?int $pid = null;

    private static function configFilename(): string
    {
        $configYamlFile = 'config-test.yml';
        $envConfigYamlFile = getenv('PHPPGADMIN_CONFIG_YAML_FILE');
        if (is_string($envConfigYamlFile)) {
            $configYamlFile = $envConfigYamlFile;
        } else {
            putenv("PHPPGADMIN_CONFIG_YAML_FILE={$configYamlFile}");
        }

        return dirname(__DIR__, 2) . "/conf/{$configYamlFile}";
    }

    public function beforeSuite(SuiteEvent $e): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();

        $config = [
            'servers' => [
                [
                    'desc' => 'Not Running Server',
                    'host' => '192.168.0.10',
                    'port' => 5432
                ],
                [
                    'desc' => 'Running Server',
                    'host' => $_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME'] ?? '127.0.0.1',
                    'port' => 5432
                ]
            ]
        ];

        file_put_contents(self::configFilename(), Yaml::dump($config));

        $configIncPhp = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.inc.php';
        $configIncPhpDist = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.inc.php-dist';
        if (!file_exists($configIncPhp) && file_exists($configIncPhpDist)) {
            copy($configIncPhpDist, $configIncPhp);
        }
    }

    public function afterSuite(SuiteEvent $e): void
    {
        unlink(self::configFilename());
    }
}
