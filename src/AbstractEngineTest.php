<?php

declare(strict_types=1);

namespace Soap\EngineIntegrationTests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Soap\Engine\Engine;
use VCR\VCR;

abstract class AbstractEngineTest extends AbstractIntegrationTest
{
    abstract protected function getEngine(): Engine;
    abstract protected function getVcrPrefix(): string;

    /**
     * Skips inserting a php-vcr cassette
     */
    abstract protected function skipVcr(): bool;

    #[RunInSeparateProcess]
    public function test_it_should_be_possible_to_hook_php_vcr_for_testing()
    {
        $this->runWithCasette('get-city-weather-by-zip-10013.yml', function () {
            $this->configureForWsdl($this->locateFixture('/wsdl/weather-ws.wsdl'));
            $result = $this->getEngine()->request('GetCityWeatherByZIP', [['ZIP' => '10013']]);
            $this->assertTrue($result->GetCityWeatherByZIPResult->Success);
        });
    }

    private function runWithCasette(string $cassete, callable $test)
    {
        if ($this->skipVcr()) {
            $test();
            return;
        }

        try {
            VCR::insertCassette($this->getVcrPrefix().$cassete);
            $test();
        } finally {
            Vcr::eject();
        }
    }
}
