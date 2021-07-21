<?php

namespace OK\Dto;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use OK\Dto\Annotation\DTO;
use OK\Dto\Exception\EntityManagerNotExistsException;
use OK\Dto\Exception\InvalidInputTypeException;
use OK\Dto\Exception\InvalidPropertyNameException;
use OK\Dto\Exception\MapperInvalidRelationException;
use OK\Dto\Exception\MapperInvalidTypeException;
use OK\Dto\Exception\MethodNotImplementedException;
use OK\Dto\Exception\NotExistValidAnnotationException;
use OK\Dto\Repository\SearchCollectionInterface;

/**
 * @author Oleg Kochetkov <oleg.kochetkov999@yandex.ru>
 */
class AnnotationMapper implements MapperInterface
{
    private const INVALID_FIELD = '_INVALID_FIELD_TOKEN';

    private Reader $reader;
    private ?EntityManagerInterface $em;

    public function __construct(Reader $reader, ?EntityManagerInterface $em = null)
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
     * @throws InvalidPropertyNameException
     * @throws NotExistValidAnnotationException
     * @throws MapperInvalidTypeException
     * @throws MapperInvalidRelationException
     * @throws MethodNotImplementedException
     * @throws EntityManagerNotExistsException
     * @throws InvalidInputTypeException
     */
    public function fillObject($object, array $data)
    {
        $name = ClassUtils::getClass($object);
        $reflClass = new \ReflectionClass($name);

        foreach ($reflClass->getMethods() as $method) {
            $annotation = $this->reader->getMethodAnnotation($method, DTO::class);

            if (!$annotation) {
                continue;
            }

            $input = $this->extractValue($annotation->name, $data);

            if ($input === self::INVALID_FIELD) {
                continue;
            }

            if ($annotation->property || !$annotation->type) {
                $propertyName = $annotation->property ?? $annotation->name;

                $dto = $this->createDTOAnnotationFromProperty($propertyName, $reflClass);

                $annotation->type = $dto->type;
                $annotation->relation = $dto->relation;
            }

            $value = null;

            if ($annotation->relation && $annotation->type) {
                $value = $this->getCustomData($annotation, $input);
            } elseif ($annotation->type) {
                $value = $this->getSimpleData($annotation, $input);
            } else {
                throw new NotExistValidAnnotationException('Use type for DTO annotation');
            }

            $methodName = $method->getName();
            $object->$methodName($value);
        }

        return $object;
    }

    private function extractValue(string $key, array $data = [])
    {
        $input = null;

        if (strrpos($key, '|') !== false) {
            $keys = explode('|', $key);

            foreach ($keys as $k) {
                $input = $this->extractValue($k, $data);

                if ($input !== self::INVALID_FIELD) {
                    break;
                }
            }
        } else {
            $input = array_key_exists($key, $data) ? $data[$key] : self::INVALID_FIELD;

            if ($input === self::INVALID_FIELD) {
                $key = $this->camelCaseToSnakeCase($key);
                $input = $data[$key] ?? self::INVALID_FIELD;
            }

            if ($input === self::INVALID_FIELD) {
                $key = $this->snakeCaseToCamelCase($key);
                $input = $data[$key] ?? self::INVALID_FIELD;
            }
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
     * @throws MapperInvalidRelationException
     * @throws MethodNotImplementedException
     * @throws EntityManagerNotExistsException
     */
    private function getCustomData(DTO $annotation, $value)
    {
        if ($this->em === null) {
            throw new EntityManagerNotExistsException();
        }

        $repository = $this->em->getRepository($annotation->type);

        switch ($annotation->relation) {
            case 'OneToMany':
                $collection = new ArrayCollection();

                if (is_string($value)) {
                    $value = json_decode($value, true);
                }

                if (!is_array($value)) {
                    $value = empty($value) ? [] : [$value];
                }

                foreach ($value as $data) {
                    $object = null;

                    if (is_numeric($data)) {
                        $object = $repository->find((int)$data);
                    } elseif (is_array($data)) {
                        $object = new $annotation->type;

                        try {
                            $object = $this->fillObject($object, $data);

                            $this->em->persist($object);
                        } catch (MapperInvalidTypeException $ex) {
                            $object = null;
                        } catch (\ReflectionException $ex) {
                            $object = null;
                        }
                    } else {
                        throw new InvalidInputTypeException('Waiting type int|array, invalid type passed to ' . $annotation->name);
                    }

                    if (isset($object)) {
                        $collection->add($object);
                    }
                }

                return $collection;
            case 'ManyToOne':
                if ($value === null) {
                    return null;
                }

                if (!is_numeric($value)) {
                    throw new InvalidInputTypeException('Waiting type int|numreic string, invalid type passed to ' . $annotation->name);
                }

                return $repository->find((int)$value);
            case 'ManyToMany':
                if (!($repository instanceof SearchCollectionInterface)) {
                    throw new MethodNotImplementedException();
                }

                if (!is_array($value)) {
                    $value = [$value];
                }

                $data =  $this->isValidManyToManyInput($value) ? $repository->findByIds($value) : [];

                return new ArrayCollection($data);
            default:
                throw new MapperInvalidRelationException(sprintf('Undefined relation type %s', $annotation->relation));
        }
    }

    /**
     * @param DTO $annotation
     * @param mixed $value
     *
     * @return mixed
     * @throws MapperInvalidTypeException|InvalidInputTypeException
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

                if (is_string($value) && !in_array($value, ['', '0', '1', 'true', 'false'])) {
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
            case 'array':
                return is_array($value) ? $value : [$value];
            default:
                throw new MapperInvalidTypeException(sprintf('Undefined type %s', $annotation->type));
        }
    }

    private function isValidManyToManyInput(array $input): bool
    {
        if (empty($input)) {
            return false;
        }

        return count($input) === count(array_filter($input, 'is_int'));
    }

    private function createException(DTO $annotation, string $type): InvalidInputTypeException
    {
        return new InvalidInputTypeException(
                sprintf('Waiting type %s, %s passed to %s',
                    $annotation->type,
                    $type,
                    $annotation->name
                )
        );
    }

    /**
     * @throws InvalidPropertyNameException|NotExistValidAnnotationException
     */
    private function createDTOAnnotationFromProperty(string $property, \ReflectionClass $reflectionClass): DTO
    {
        $dtoAnnotation = new DTO();
        $dtoAnnotation->name = $property;

        try {
            $reflProperty = $reflectionClass->getProperty($property);
        } catch (\ReflectionException $ex) {
            throw new InvalidPropertyNameException("Property with name '$property' doesn't exist in class " . $reflectionClass->getName());
        }

        $columnAnnotation = $this->reader->getPropertyAnnotation($reflProperty, Column::class);

        if ($columnAnnotation) {
            $dtoAnnotation->type = $columnAnnotation->type;

            return $dtoAnnotation;
        }

        $manyToManyAnnotation = $this->reader->getPropertyAnnotation($reflProperty, ManyToMany::class);

        if ($manyToManyAnnotation) {
            $dtoAnnotation->relation = 'ManyToMany';
            $dtoAnnotation->type = $manyToManyAnnotation->targetEntity;

            return $dtoAnnotation;
        }

        $oneToManyAnnotation = $this->reader->getPropertyAnnotation($reflProperty, OneToMany::class);

        if ($oneToManyAnnotation) {
            $dtoAnnotation->relation = 'OneToMany';
            $dtoAnnotation->type = $oneToManyAnnotation->targetEntity;

            return $dtoAnnotation;
        }

        $manyToOneAnnotation = $this->reader->getPropertyAnnotation($reflProperty, ManyToOne::class);

        if ($manyToOneAnnotation) {
            $dtoAnnotation->relation = 'ManyToOne';
            $dtoAnnotation->type = $manyToOneAnnotation->targetEntity;

            return $dtoAnnotation;
        }

        $message = sprintf(
            'Use type and relation for DTO annotation or use one of Doctrine annotation (%s, %s, %s, %s) for property',
            Column::class,
            ManyToMany::class,
            ManyToOne::class,
            OneToMany::class
        );

        throw new NotExistValidAnnotationException($message);
    }
}
