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
        $mockEm = $this->createMock(EntityManager::class);

        $mapper = new AnnotationMapper(new AnnotationReader(), $mockEm);

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

    private function isValidManyToManyInput($input): bool
    {
        $value = is_array($input) ? $input : (array)$input;
        $firstValue = reset($value);

        return is_numeric($firstValue);
    }
}
