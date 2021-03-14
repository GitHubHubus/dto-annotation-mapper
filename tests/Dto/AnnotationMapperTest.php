<?php

namespace Tests\Dto;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use OK\Dto\AnnotationMapper;
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

    private function getSimpleMapperMock()
    {
        $mockEm = $this->createMock(EntityManager::class);

        return new AnnotationMapper(new AnnotationReader(), $mockEm);
    }
}
