<?php

declare(strict_types=1);

namespace Soap\EngineIntegrationTests;

use DOMElement;
use PHPUnit\Framework\TestCase;
use Soap\Xml\Locator\SoapBodyLocator;
use Soap\Xml\Xpath\EnvelopePreset;
use VeeWee\Xml\Dom\Collection\NodeList;
use VeeWee\Xml\Dom\Document;

abstract class AbstractIntegrationTest extends TestCase
{
    abstract protected function configureForWsdl(string $wsdl);

    protected function locateFixture(string $fixture): string {
        $path = __DIR__.'/../fixtures' . $fixture;

        self::assertFileExists($path);

        return $path;
    }

    protected function runXpathOnBody(Document $xml, string $xpath): NodeList
    {
        $body = $xml->locate(new SoapBodyLocator());
        $results = $xml->xpath(new EnvelopePreset($xml))->query($xpath, $body);

        $this->assertGreaterThan(0, $results->count());

        return $results;
    }

    protected function runSingleElementXpathOnBody(Document $xml, string $xpath): DOMElement
    {
        $body = $xml->locate(new SoapBodyLocator());

        return $xml->xpath(new EnvelopePreset($xml))->querySingle($xpath, $body);
    }
}
