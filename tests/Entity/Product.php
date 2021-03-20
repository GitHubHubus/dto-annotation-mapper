<?php

namespace Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OK\Dto\Annotation\DTO;

/**
 * @ORM\Entity(repositoryClass="OK\DTO\Repository\EntityRepository")
 */
class Product
{
    /**
     * @var string
     *
     */
    protected $article;

    /**
     * @ORM\ManyToMany(targetEntity="Tests\Entity\Material")
     */
    protected $materials;

    /**
     * @var Material
     *
     * @ORM\ManyToOne(targetEntity="Tests\Entity\Material")
     */
    protected $material;


    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $price;

    /**
     * @ORM\OneToMany(targetEntity="Tests\Entity\Material", mappedBy="product")
     */
    protected $materials2;

    public function __construct()
    {
        $this->materials = new ArrayCollection();
        $this->materials2 = new ArrayCollection();
    }

    public function getArticle(): ?string
    {
        return $this->article;
    }

    /**
     * @DTO(name="article", type="string")
     */
    public function setArticle(?string $article)
    {
        $this->article = $article;
    }

    /**
     * @DTO(name="material", type="Tests\Entity\Material", relation="ManyToOne")
     */
    public function setMaterial(?Material $material)
    {
        $this->material = $material;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price)
    {
        $this->price = $price;
    }

    /**
     * @DTO(name="metarials", type="Tests\Entity\Materials", relation="ManyToMany")
     */
    public function setMaterials(?Collection $materials)
    {
        $this->materials = $materials;
    }

    public function getMaterials(): ?Collection
    {
        return $this->materials;
    }

    /**
     * @DTO(name="metarials2", type="Tests\Entity\Materials", relation="ManyToMany")
     */
    public function setMaterials2(?Collection $materials)
    {
        $this->materials2 = $materials;
    }

    public function getMaterials2(): ?Collection
    {
        return $this->materials2;
    }
}
