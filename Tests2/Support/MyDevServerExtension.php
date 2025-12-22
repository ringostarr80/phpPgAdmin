<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Event\SuiteEvent;
use Codeception\Events;

class MyDevServerExtension extends \Codeception\Extension
{
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
        chdir(dirname(__DIR__, 2));

        $command = sprintf(
            'php -S 0.0.0.0:9876 -d auto_prepend_file=%s -d xdebug.mode=coverage',
            escapeshellarg(dirname(__DIR__, 2) . '/c3.php')
        );
        $fullCommand = "nohup {$command} > /dev/null 2>&1 & echo $!";
        $output = shell_exec($fullCommand);
        if (is_string($output)) {
            $trimmedOutput = trim($output);
            if (is_numeric($trimmedOutput)) {
                $this->pid = (int)$trimmedOutput;
            }
        }
        usleep(1_000_000);
    }

    public function afterSuite(SuiteEvent $e): void
    {
        if (!is_null($this->pid)) {
            $killCommand = 'kill ' . $this->pid;
            shell_exec($killCommand);
        }
    }
}
