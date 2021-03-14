<?php

namespace OK\Dto;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use OK\Dto\Annotation\DTO;
use OK\Dto\Exception\InvalidInputTypeException;
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

            $value = ($annotation->relation && $annotation->type) ?
                $this->getCustomData($annotation, $input) :
                $this->getSimpleData($annotation, $input);

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
        if (empty($string)) {
            return '';
        }

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
    private function getCustomData(DTO $annotation, $value)
    {
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
    }

    /**
     * @param DTO $annotation
     * @param mixed $value
     *
     * @return mixed
     * @throws MapperInvalidTypeException
     */
    private function getSimpleData(DTO $annotation, $value)
    {
        if (is_null($value)) {
            return null;
        }

        switch ($annotation->type) {
            case 'float':
                if (is_bool($value)) {
                    throw $this->createException($annotation, 'boolean');
                }

                if (is_array($value)) {
                    throw $this->createException($annotation, 'array');
                }

                if (is_string($value) && !is_numeric($value)) {
                    throw $this->createException($annotation, 'string');
                }

                return (float)$value;
            case 'int':
            case 'integer':
                if (is_bool($value)) {
                    throw $this->createException($annotation, 'boolean');
                }

                if (is_array($value)) {
                    throw $this->createException($annotation, 'array');
                }

                if (is_string($value) && !is_numeric($value)) {
                    throw $this->createException($annotation, 'string');
                }

                return (int)$value;
            case 'string':
                if (is_bool($value)) {
                    throw $this->createException($annotation, 'boolean');
                }

                if (is_array($value)) {
                    throw $this->createException($annotation, 'array');
                }

                return (string)$value;
            case 'bool':
            case 'boolean':
                if (is_array($value)) {
                    throw $this->createException($annotation, 'array');
                }

                if (is_string($value) && !in_array($value, ['0', '1', 'true', 'false'])) {
                    throw $this->createException($annotation, 'string');
                }

                return (bool)$value;
            case 'datetime':
                if (is_bool($value)) {
                    throw $this->createException($annotation, 'boolean');
                }

                if (is_array($value)) {
                    throw $this->createException($annotation, 'array');
                }

                if (is_integer($value)) {
                    throw $this->createException($annotation, 'integer');
                }

                if (is_float($value)) {
                    throw $this->createException($annotation, 'float');
                }

                try {
                    return new \DateTime($value);
                } catch (\Throwable $exception) {
                    throw new InvalidInputTypeException('Datetime create exception: ' . $exception->getMessage());
                }
            default:
                throw new MapperInvalidTypeException(sprintf('Undefined type %s', $annotation->type));
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

    private function createException(DTO $annotation, string $type): InvalidInputTypeException
    {
        return new InvalidInputTypeException(
                sprintf('Waiting type %s, %s passed to $s',
                    $annotation->type,
                    $type,
                    $annotation->name
                )
        );
    }
}
