<?php

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use OK\Dto\Annotation\DTO;

/**
 * @ORM\Entity(repositoryClass="OK\Dto\Repository\EntityRepository")
 */
class Material
{
    protected $id;

    protected $name;

    public function __construct(?int $id = null, ?string $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @DTO(name="id", type="int")
     */
    public function setStatus(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @DTO(name="name", type="string")
     */
    public function setName(?string $name)
    {
        return $this->name = $name;
    }

}
