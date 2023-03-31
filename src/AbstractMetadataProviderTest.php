<?php

declare(strict_types=1);

namespace Soap\EngineIntegrationTests;

use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\ParameterCollection;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\MetadataProvider;
use Soap\Engine\Metadata\Model\Parameter;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\XsdType;

abstract class AbstractMetadataProviderTest extends AbstractIntegrationTest
{
    abstract protected function getMetadataProvider(): MetadataProvider;

    public function test_it_can_load_wsdl_methods()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/string.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        static::assertCount(1, $methods);
        self::assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', XsdType::guess('string'))
            ],
            XsdType::guess('string')
        );
    }

    public function test_it_can_load_wsdl_method_with_multiple_response_arguments()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/multiArgumentResponse.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        static::assertCount(1, $methods);
        self::assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', XsdType::guess('string'))
            ],
            XsdType::guess('array')
        );
    }

    public function test_it_can_load_union_types_in_methods()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/union.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        $jeansType = XsdType::guess('jeansSize')
            ->withBaseType('anyType')
            ->withMemberTypes(['sizebyno', 'sizebystring']);

        static::assertCount(1, $methods);
        self::assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', $jeansType)
            ],
            $jeansType
        );
    }

    public function test_it_can_load_list_types_in_methods()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/list.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        $listType = XsdType::guess('valuelist')
            ->withBaseType('array')
            ->withMemberTypes(['integer']);

        static::assertCount(1, $methods);
        self::assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', $listType)
            ],
            $listType
        );
    }

    public function test_it_can_load_simple_content_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/simpleContent.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        //var_dump($types->fetchFirstByName('SimpleContent'));exit;

        static::assertCount(1, $types);
        self::assertTypeExists(
            $types,
            XsdType::guess('SimpleContent'),
            [
                new Property('_', XsdType::guess('integer')->withBaseType('float')->withMemberTypes(['decimal'])),
                new Property('country', XsdType::guess('string')),
            ]
        );
    }

    public function test_it_can_load_complex_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/complex-type-request-response.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        static::assertCount(2, $types);
        self::assertTypeExists(
            $types,
            XsdType::guess('ValidateRequest'),
            [
                new Property('input', XsdType::guess('string')->withBaseType('anySimpleType'))
            ]
        );
        self::assertTypeExists(
            $types,
            XsdType::guess('ValidateResponse'),
            [
                new Property('output', XsdType::guess('string')->withBaseType('anySimpleType'))
            ]
        );
    }

    public function test_it_can_load_union_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/union.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        $jeansType = XsdType::guess('jeansSize')
            ->withBaseType('anyType')
            ->withMemberTypes(['sizebyno', 'sizebystring']);


        self::assertTypeExists(
            $types,
            XsdType::guess('jeansSizeContainer'),
            [
                new Property('jeansSize', $jeansType)
            ]
        );
    }

    public function test_it_can_load_list_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/list.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        $listType = XsdType::guess('valuelist')
           ->withBaseType('array')
           ->withMemberTypes(['integer']);

        self::assertTypeExists(
            $types,
            XsdType::guess('valuelistContainer'),
            [
                new Property('valuelist', $listType)
            ]
        );
    }

    public function test_it_can_handle_duplicate_type_declarations()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/duplicate-typenames.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        static::assertCount(2, $types);

        $type1 = $types->getIterator()[1];
        static::assertSame('Store', $type1->getName());
        static::assertXsdTypeMatches(XsdType::guess('Store'), $type1->getXsdType());
        static::assertPropertiesMatch(
            new PropertyCollection(new Property('Attribute2', XsdType::guess('string')->withBaseType('anySimpleType'))),
            $type1->getProperties()
        );

        $type2 = $types->getIterator()[1];
        static::assertSame('Store', $type2->getName());
        static::assertXsdTypeMatches(XsdType::guess('Store'), $type2->getXsdType());
        static::assertPropertiesMatch(
            new PropertyCollection(new Property('Attribute2', XsdType::guess('string')->withBaseType('anySimpleType'))),
            $type2->getProperties()
        );
    }

    private static function assertMethodExists(MethodCollection $methods, string $name, array $parameters, XsdType $returnType)
    {
        $method = $methods->fetchByName($name);
        static::assertSame($name, $method->getName());
        static::assertParametersMatch(new ParameterCollection(...$parameters), $method->getParameters());
        static::assertXsdTypeMatches($returnType, $method->getReturnType());
    }

    private static function assertTypeExists(TypeCollection $types, XsdType $xsdType, array $properties)
    {
        $type = $types->fetchFirstByName($xsdType->getName());
        static::assertSame($xsdType->getName(), $type->getName());
        static::assertEquals($xsdType->getName(), $type->getXsdType());
        static::assertPropertiesMatch(new PropertyCollection(...$properties), $type->getProperties());
    }

    private static function assertParametersMatch(ParameterCollection $expected, ParameterCollection $actual)
    {
        $expectedList = [...$expected];
        static::assertCount(count($expectedList), $actual);
        foreach ($actual as $index => $current) {
            self::assertParameterMatch($expectedList[$index], $current);
        }
    }

    private static function assertParameterMatch(Parameter $expected, Parameter $actual)
    {
        static::assertSame($expected->getName(), $actual->getName());
        self::assertXsdTypeMatches($expected->getType(), $actual->getType());
    }

    private static function assertPropertyMatch(Property $expected, Property $actual)
    {
        static::assertSame($expected->getName(), $actual->getName());
        self::assertXsdTypeMatches($expected->getType(), $actual->getType());
    }

    private static function assertPropertiesMatch(PropertyCollection $expected, PropertyCollection $actual)
    {
        $expectedList = [...$expected];
        static::assertCount(count($expectedList), $actual);
        foreach ($actual as $index => $current) {
            self::assertPropertyMatch($expectedList[$index], $current);
        }
    }

    private static function assertXsdTypeMatches(XsdType $expected, XsdType $actual)
    {
        static::assertSame($expected->getName(), $actual->getName());

        // Base type will be checked optionally:
        // ext-soap does not have an extended overview of all inherited types.
        if ($actual->getBaseType()) {
            static::assertSame($expected->getBaseType(), $actual->getBaseType());
        }

        // Member types will be checked optionally:
        // ext-soap does not have an extended overview of all inherited types.
        if ($actual->getMemberTypes()) {
            static::assertSame($expected->getMemberTypes(), $actual->getMemberTypes());
        }

        // Not validating namespace info + metadata in here.
        // Since implementations like ext-soap cannot provide this information.
    }
}
