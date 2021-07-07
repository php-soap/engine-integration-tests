<?php

declare(strict_types=1);

namespace Soap\EngineIntegrationTests;

use Soap\Engine\Metadata\Collection\MethodCollection;
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
        $this->assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', XsdType::create('string'))
            ],
            XsdType::create('string')
        );
    }

    
    public function test_it_can_load_wsdl_method_with_multiple_response_arguments()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/multiArgumentResponse.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        static::assertCount(1, $methods);
        $this->assertMethodExists(
            $methods,
            'validate',
            [
                new Parameter('input', XsdType::create('string'))
            ],
            XsdType::create('array')
        );
    }

    
    public function test_it_can_load_union_types_in_methods()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/union.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $methods = $metadata->getMethods();

        $jeansType = XsdType::create('jeansSize')
            ->withBaseType('anyType')
            ->withMemberTypes(['sizebyno', 'sizebystring']);

        static::assertCount(1, $methods);
        $this->assertMethodExists(
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

        $listType = XsdType::create('valuelist')
            ->withBaseType('array')
            ->withMemberTypes(['integer']);

        static::assertCount(1, $methods);
        $this->assertMethodExists(
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

        static::assertCount(1, $types);
        $this->assertTypeExists(
            $types,
            XsdType::create('SimpleContent'),
            [
                new Property('_', XsdType::create('integer')),
                new Property('country', XsdType::create('string')),
            ]
        );
    }

    
    public function test_it_can_load_complex_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/complex-type-request-response.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        static::assertCount(2, $types);
        $this->assertTypeExists(
            $types,
            XsdType::create('ValidateRequest'),
            [
                new Property('input', XsdType::create('string'))
            ]
        );
        $this->assertTypeExists(
            $types,
            XsdType::create('ValidateResponse'),
            [
                new Property('output', XsdType::create('string'))
            ]
        );
    }

    
    public function test_it_can_load_union_types()
    {
        $this->configureForWsdl($this->locateFixture('/wsdl/functional/union.wsdl'));

        $metadata = $this->getMetadataProvider()->getMetadata();
        $types = $metadata->getTypes();

        $jeansType = XsdType::create('jeansSize')
            ->withBaseType('anyType')
            ->withMemberTypes(['sizebyno', 'sizebystring']);

        static::assertCount(1, $types);
        $this->assertTypeExists(
            $types,
            XsdType::create('jeansSizeContainer'),
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

        $listType = XsdType::create('valuelist')
           ->withBaseType('array')
           ->withMemberTypes(['integer']);

        static::assertCount(1, $types);
        $this->assertTypeExists(
            $types,
            XsdType::create('valuelistContainer'),
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
        static::assertEquals(XsdType::create('Store'), $type1->getXsdType());
        static::assertEquals([new Property('Attribute2', XsdType::create('string'))], [...$type1->getProperties()]);

        $type2 = $types->getIterator()[1];
        static::assertSame('Store', $type2->getName());
        static::assertEquals(XsdType::create('Store'), $type2->getXsdType());
        static::assertEquals([new Property('Attribute2', XsdType::create('string'))], [...$type2->getProperties()]);
    }
    
    private function assertMethodExists(MethodCollection $methods, string $name, array $parameters, XsdType $returnType)
    {
        $method = $methods->fetchByName($name);
        static::assertSame($name, $method->getName());
        static::assertEquals($parameters, [...$method->getParameters()]);
        static::assertEquals($returnType, $method->getReturnType());
    }

    private function assertTypeExists(TypeCollection $types, XsdType $xsdType, array $properties)
    {
        $type = $types->fetchFirstByName($xsdType->getName());
        static::assertSame($xsdType->getName(), $type->getName());
        static::assertEquals($xsdType->getName(), $type->getXsdType());
        static::assertEquals($properties, [...$type->getProperties()]);
    }
}
