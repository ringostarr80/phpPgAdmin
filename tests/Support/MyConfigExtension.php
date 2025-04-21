<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class MyConfigExtension extends \Codeception\Extension
{
    public const NOT_RUNNING_SERVER_DESC = 'Not Running Server';
    public const RUNNING_SERVER_DESC = 'Running Server';

    private static bool $configIncPhpCreated = false;
    /**
     * @var array<mixed>
     */
    public static array $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::SUITE_AFTER => 'afterSuite'
    ];
    protected ?int $pid = null;

    public function beforeSuite(SuiteEvent $e): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();

        $envServerHostname = '127.0.0.1';
        if (isset($_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME']) && is_string($_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME'])) {
            $envServerHostname = $_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME'];
        } else {
            $getEnvServerHostname = getenv('PHPPGADMIN_TEST_SERVER_HOSTNAME');
            if (is_string($getEnvServerHostname)) {
                $envServerHostname = $getEnvServerHostname;
            }
        }

        $config = [
            'servers' => [
                [
                    'desc' => 'Not Running Server',
                    'host' => '192.168.0.10',
                    'port' => 5432
                ],
                [
                    'desc' => 'Running Server',
                    'host' => $envServerHostname,
                    'port' => 5432
                ]
            ]
        ];

        file_put_contents(self::configFilename(), Yaml::dump($config));

        $configIncPhp = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.inc.php';
        $configIncPhpDist = $configIncPhp . '-dist';
        if (!file_exists($configIncPhp) && file_exists($configIncPhpDist) && copy($configIncPhpDist, $configIncPhp)) {
            self::$configIncPhpCreated = true;

            $configIncPhpContent = file_get_contents($configIncPhp) ?:
                throw new \RuntimeException('Failed to read config.inc.php');

            $configIncPhpContent = str_replace(
                "\$conf['servers'][0]['desc'] = 'PostgreSQL';",
                "\$conf['servers'][0]['desc'] = '" . self::RUNNING_SERVER_DESC . "';",
                $configIncPhpContent
            );

            $configIncPhpContent = str_replace(
                "\$conf['servers'][0]['host'] = '';",
                "\$conf['servers'][0]['host'] = '" . $envServerHostname . "';",
                $configIncPhpContent
            );

            $configIncPhpContent = str_replace(
                "\$conf['extra_login_security'] = true;",
                "\$conf['extra_login_security'] = false;",
                $configIncPhpContent
            );

            file_put_contents($configIncPhp, $configIncPhpContent) ?:
                throw new \RuntimeException('Failed to write config.inc.php');
        }
    }

    public function afterSuite(SuiteEvent $e): void
    {
        unlink(self::configFilename());
        if (self::$configIncPhpCreated) {
            $configIncPhp = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'config.inc.php';
            if (file_exists($configIncPhp)) {
                unlink($configIncPhp);
            }
        }
    }

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

    public static function getEnvVar(string $envName): ?string
    {
        $value = null;
        if (isset($_ENV[$envName]) && is_string($_ENV[$envName])) {
            $value = $_ENV[$envName];
        } else {
            $envValue = getenv($envName);
            if (is_string($envValue)) {
                $value = $envValue;
            }
        }

        return $value;
    }
}
