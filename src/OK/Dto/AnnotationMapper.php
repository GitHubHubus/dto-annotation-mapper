<?php

namespace OK\Dto;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use OK\Dto\Annotation\DTO;
use OK\Dto\Exception\MapperInvalidTypeException;
use OK\Dto\Exception\MethodNotImplementedException;
use OK\Dto\Repository\SearchCollectionInterface;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class AnnotationMapper implements MapperInterface
{
    private const INVALID_FIELD = '_INVALID_FIELD_TOKEN';

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param Reader $reader
     * @param EntityManagerInterface $em
     */
    public function __construct(Reader $reader, EntityManagerInterface $em)
    {
        $this->reader = $reader;
        $this->em = $em;
    }

    /**
     * @param mixed $object
     * @param array $data
     *
     * @return mixed
     * @throws MapperInvalidTypeException
     * @throws \ReflectionException
     */
    public function fillObject($object, array $data)
    {
        $reflClass = new \ReflectionClass($object);

        foreach ($reflClass->getMethods() as $method) {
            $annotation = $this->reader->getMethodAnnotation($method, DTO::class);

            if (!$annotation) {
                continue;
            }

            $input = $this->extractValue($annotation->name, $data);

            if ($input === self::INVALID_FIELD) {
                continue;
            }

            $value = $this->getData($annotation, $input);
            $methodName = $method->getName();
            $object->$methodName($value);
        }

        return $object;
    }

    private function extractValue(string $key, array $data = [])
    {
        $input = array_key_exists($key, $data) ? $data[$key] : self::INVALID_FIELD;

        if ($input === self::INVALID_FIELD) {
            $key = $this->camelCaseToSnakeCase($key);
            $input = $data[$key] ?? self::INVALID_FIELD;
        }

        if ($input === self::INVALID_FIELD) {
            $key = $this->snakeCaseToCamelCase($key);
            $input = $data[$key] ?? self::INVALID_FIELD;
        }

        return $input;
    }

    private function camelCaseToSnakeCase(string $string): string
    {
        $parts = preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY);
        $new = array_shift($parts);

        foreach($parts as $part) {
            $new .= '_' . lcfirst($part);
        }

        return $new;
    }

    private function snakeCaseToCamelCase(string $string): string
    {
        if (empty($string)) {
            return '';
        }

        $parts = explode('_', $string);
        $new = array_shift($parts);

        if (empty($new)) {
            $new = array_shift($parts);
        }

        foreach($parts as $part) {
            $new .= ucfirst($part);
        }

        return $new;
    }

    /**
     * @param DTO $annotation
     * @param mixed $value
     *
     * @return mixed
     * @throws MapperInvalidTypeException
     * @throws MethodNotImplementedException
     */
    private function getData(DTO $annotation, $value)
    {
        if ($annotation->relation && $annotation->type) {
            $repository = $this->em->getRepository($annotation->type);

            switch ($annotation->relation) {
                case 'OneToMany':
                    $collection = new ArrayCollection();

                    if (is_string($value)) {
                        $value = json_decode($value, true);
                    }

                    foreach ($value as $data) {
                        if (is_numeric($data)) {
                            $object = $repository->find((int)$data);
                        } elseif (is_array($data)) {
                            $object = new $annotation->type;
                            $object = $this->fillObject($object, $data);

                            $this->em->persist($object);
                        }
                        if (isset($object)) {
                            $collection->add($object);
                        }
                    }

                    return $collection;
                case 'ManyToOne':
                    return $repository->find((int)$value);
                case 'ManyToMany':
                    if (!($repository instanceof SearchCollectionInterface)) {
                        throw new MethodNotImplementedException();
                    }

                    $data =  $this->isValidManyToManyInput($value) ? $repository->findByIds($value) : [];

                    return new ArrayCollection($data);
            }
        } else {
            switch ($annotation->type) {
                case 'float':
                    return (float)$value;
                case 'int':
                case 'integer':
                    return (int)$value;
                case 'string':
                    return $value;
                case 'bool':
                case 'boolean':
                    return (bool)$value;
                case 'datetime':
                    return new \DateTime($value);
                default:
                    throw new MapperInvalidTypeException(sprintf('Undefined type %s', $annotation->type));
            }
        }
    }

    private function isValidManyToManyInput($input): bool
    {
        $value = is_array($input) ? $input : (array)$input;

        if (empty($value)) {
            return false;
        }

        return count($value) === count(array_filter($value, 'is_int'));
    }
}
