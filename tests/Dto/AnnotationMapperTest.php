<?php

namespace Tests\Dto;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use OK\Dto\Annotation\DTO;
use OK\Dto\AnnotationMapper;
use OK\Dto\Exception\InvalidInputTypeException;
use Tests\Entity\Material;
use Tests\Entity\Product;
use Tests\TestCase;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class AnnotationMapperTest extends TestCase
{
    /**
     * @dataProvider isValidManyToManyInputProvider
     */
    public function testIsValidManyToManyInput($input, $result)
    {
        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'isValidManyToManyInput');

        $this->assertEquals($result, $method->invokeArgs($mapper, [$input]));
    }

    public function isValidManyToManyInputProvider()
    {
        return [
            ['', false],
            [null, false],
            [1, true],
            ['2', false],
            ['f', false],
            [true, false],
            [false, false],
            [['1', 2, 3], false],
            [[0, 1, 2], true],
            [[2, 3, true], false],
            [[2, 3, 'g'], false],
        ];
    }

    /**
     * @dataProvider snakeCaseToCamelCaseProvider
     */
    public function testSnakeCaseToCamelCase($input, $result)
    {
        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'snakeCaseToCamelCase');

        $this->assertEquals($result, $method->invokeArgs($mapper, [$input]));
    }

    public function snakeCaseToCamelCaseProvider()
    {
        return [
            ['testcase', 'testcase'],
            ['', ''],
            ['test_case', 'testCase'],
            ['test_case_2', 'testCase2'],
            ['_test_case_3', 'testCase3'],
            ['testCase4', 'testCase4'],
        ];
    }

    /**
     * @dataProvider camelCaseToSnakeCaseProvider
     */
    public function testCamelCaseToSnakeCase($input, $result)
    {
        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'camelCaseToSnakeCase');

        $this->assertEquals($result, $method->invokeArgs($mapper, [$input]));
    }

    public function camelCaseToSnakeCaseProvider()
    {
        return [
            ['testcase', 'testcase'],
            ['', ''],
            ['testCase', 'test_case'],
            ['testCase2', 'test_case2'],
            ['testCase_3', 'test_case_3'],
            ['testCaseCase', 'test_case_case'],
        ];
    }

    /**
     * @dataProvider extractValueProvider
     */
    public function testExtractValue($input, $key, $result)
    {
        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'extractValue');

        $this->assertEquals($result, $method->invokeArgs($mapper, [$key, $input]));
    }

    public function extractValueProvider()
    {
        return [
            [['testkey' => 'value'], 'testkey', 'value'],
            [['testKey' => 'value'], 'testKey', 'value'],
            [['testKey' => 'value'], 'test_key', 'value'],
            [['test_key' => 'value'], 'test_key', 'value'],
            [['test_key' => 'value'], 'testKey', 'value'],
            [['test_key' => 'value', 'testKey' => 'value2'], 'testKey', 'value2'],
            [['test_key' => 'value', 'testKey' => 'value2'], 'test_key', 'value'],
            [['test_key' => 'value'], 'invalidTestKey', '_INVALID_FIELD_TOKEN'],
        ];
    }

    /**
     * @dataProvider getSimpleDataFloatProvider
     */
    public function testGetSimpleDataFloat($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'float';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    public function getSimpleDataFloatProvider()
    {
        return [
            ['1', 1.0],
            ['0', 0],
            ['-1.1', -1.1],
            ['+1.1', 1.1],
            [0, 0],
            [1, 1.0],
            [-1, -1.0],
            [null, null],
            [true, 'Exception'],
            [false, 'Exception'],
            ['test', 'Exception'],
            [[], 'Exception'],
            [[1.0], 'Exception'],
        ];
    }

    /**
     * @dataProvider getSimpleDataIntProvider
     */
    public function testGetSimpleDataInt($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'int';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    /**
     * @dataProvider getSimpleDataIntProvider
     */
    public function testGetSimpleDataInteger($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'integer';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    public function getSimpleDataIntProvider()
    {
        return [
            ['1', 1],
            ['+1', 1],
            ['0', 0],
            ['1.1', 1],
            ['-1.1', -1],
            [0, 0],
            [-1, -1],
            [1.0, 1],
            [null, null],
            [true, 'Exception'],
            [false, 'Exception'],
            ['test', 'Exception'],
            [[], 'Exception'],
            [[1], 'Exception'],
        ];
    }

    /**
     * @dataProvider getSimpleDataStringProvider
     */
    public function testGetSimpleDataString($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'string';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    public function getSimpleDataStringProvider()
    {
        return [
            ['1', '1'],
            ['+1', '+1'],
            ['0', '0'],
            [1.1, '1.1'],
            [-1.1, '-1.1'],
            [null, null],
            [true, 'Exception'],
            [false, 'Exception'],
            ['test', 'test'],
            [[], 'Exception'],
            [[1], 'Exception'],
        ];
    }

    /**
     * @dataProvider getSimpleDataBoolProvider
     */
    public function testGetDataBool($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'bool';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    /**
     * @dataProvider getSimpleDataBoolProvider
     */
    public function testGetDataBoolean($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'boolean';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    public function getSimpleDataBoolProvider()
    {
        return [
            ['1', true],
            ['0', false],
            [1, true],
            [0, false],
            [null, null],
            [true, true],
            [false, false],
            ['test', 'Exception'],
            [[], 'Exception'],
            [[1], 'Exception'],
        ];
    }

    /**
     * @dataProvider getSimpleDataDatetimeProvider
     */
    public function testGetDataDatetime($value, $result)
    {
        $annotation = new DTO();
        $annotation->type = 'datetime';

        $mapper = $this->getSimpleMapperMock();
        $method = $this->makeCallable($mapper, 'getSimpleData');

        if ($result === 'Exception') {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $value]));
    }

    public function getSimpleDataDatetimeProvider()
    {
        return [
            ['12.02.2020', new \DateTime('12.02.2020')],
            ['12/02/2020', new \DateTime('12/02/2020')],
            ['12-02-2020', new \DateTime('12-02-2020')],
            ['0', 'Exception'],
            [1, 'Exception'],
            [0, 'Exception'],
            [null, null],
            [true, 'Exception'],
            [false, 'Exception'],
            ['test', 'Exception'],
            [[], 'Exception'],
            [[1], 'Exception'],
        ];
    }

    /**
     * @dataProvider getCustomDataOneToManyProvider
     */
    public function testGetCustomDataOneToMany($input, $result, $entities, $exception = false)
    {
        $annotation = new DTO();
        $annotation->type = 'Tests\Entity\Material';
        $annotation->relation = 'OneToMany';

        $map = [
            [1, null, null, $entities[1]],
            [2, null, null, $entities[2]],
            [3, null, null, $entities[3]]
        ];

        $mockRep = $this->createMock(EntityRepository::class);
        $mockRep->method('find')->will($this->returnValueMap($map));

        $mockEm = $this->createMock(EntityManager::class);
        $mockEm->method('getRepository')->willReturn($mockRep);

        $mapper = new AnnotationMapper(new AnnotationReader(), $mockEm);

        $method = $this->makeCallable($mapper, 'getCustomData');

        if ($exception) {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $input]));
    }

    public function getCustomDataOneToManyProvider()
    {
        $entities = [
            1 => new Material(1, 'm1'),
            2 => new Material(2, 'm2'),
            3 => new Material(3, 'm3')
        ];

        return [
            ['1', new ArrayCollection([$entities[1]]), $entities],
            [1, new ArrayCollection([$entities[1]]), $entities],
            [[1, 2], new ArrayCollection([$entities[1], $entities[2]]), $entities],
            [[1, 4], new ArrayCollection([$entities[1]]), $entities],
            ['[1, 2]', new ArrayCollection([$entities[1], $entities[2]]), $entities],
            ['["1", "2"]', new ArrayCollection([$entities[1], $entities[2]]), $entities],
            ['[1, "f"]', new ArrayCollection([$entities[1]]), $entities, true],
            [[['id' => 3, 'name' => 'm3']], new ArrayCollection([$entities[3]]), $entities],
            [[2, ['id' => 3, 'name' => 'm3']], new ArrayCollection([$entities[2], $entities[3]]), $entities],
            [[null, ['id' => 3, 'name' => 'm3']], new ArrayCollection([$entities[3]]), $entities, true],
            [[true, ['id' => 3, 'name' => 'm3']], new ArrayCollection([$entities[3]]), $entities, true]
        ];
    }

    /**
     * @dataProvider getCustomDataManyToOneProvider
     */
    public function testGetCustomDataManyToOne($input, $result, $exception = false)
    {
        $annotation = new DTO();
        $annotation->type = 'Tests\Entity\Material';
        $annotation->relation = 'ManyToOne';

        $mockRep = $this->createMock(EntityRepository::class);
        $mockRep->method('find')->willReturn(new Material(1, 'm1'));

        $mockEm = $this->createMock(EntityManager::class);
        $mockEm->method('getRepository')->willReturn($mockRep);

        $mapper = new AnnotationMapper(new AnnotationReader(), $mockEm);

        $method = $this->makeCallable($mapper, 'getCustomData');

        if ($exception) {
            $this->expectException(InvalidInputTypeException::class);
        }

        $this->assertEquals($result, $method->invokeArgs($mapper, [$annotation, $input]));
    }

    public function getCustomDataManyToOneProvider()
    {
        return [
            ['1', new Material(1, 'm1')],
            [1, new Material(1, 'm1')],
            [true, null, true],
            [null, null, true],
            ['f', null, true],
            [[], null, true],
            [[1], null, true],
        ];
    }

    private function getSimpleMapperMock()
    {
        $mockEm = $this->createMock(EntityManager::class);

        return new AnnotationMapper(new AnnotationReader(), $mockEm);
    }
}
