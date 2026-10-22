<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ApplicationBootstrapTest extends TestCase
{
    public function testWebEntrypointLoadsComposerBeforeDeploymentSpecificBootstrap(): void
    {
        $source = file_get_contents(__DIR__ . '/../../index.php');
        self::assertIsString($source);

        $composerBootstrap = 'require_once __DIR__ . "/vendor/autoload.php";';
        $deploymentBootstrap = 'require_once "includes/include.php";';
        $composerPosition = strpos($source, $composerBootstrap);
        $deploymentPosition = strpos($source, $deploymentBootstrap);

        self::assertNotFalse($composerPosition, 'The web entrypoint must load Composer dependencies.');
        self::assertNotFalse($deploymentPosition, 'The deployment-specific bootstrap is missing.');
        self::assertLessThan(
            $deploymentPosition,
            $composerPosition,
            'Composer must be available independently of the Git-ignored deployment bootstrap.'
        );
    }
}
