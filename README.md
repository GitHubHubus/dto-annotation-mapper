# Annotation entity mapper based on Doctrine 

[![Latest Stable Version](https://poser.pugx.org/ok/dto-annotation-mapper/version)](https://packagist.org/packages/ok/dto-annotation-mapper)
[![Total Downloads](https://poser.pugx.org/ok/dto-annotation-mapper/downloads)](https://packagist.org/packages/ok/dto-annotation-mappert)
[![License](https://poser.pugx.org/ok/dto-annotation-mapper/license)](https://packagist.org/packages/ok/dto-annotation-mapper)

This library intended for auto fill entity object from input dataset based on annotations.

## Installation

You can install the package via composer:

```bash
composer require ok/dto-annotation-mapper
```

For Symfony project:
Register AnnotationMapper as service in `services.yml`:
```yaml
OK\Dto\MapperInterface:
   class: OK\Dto\AnnotationMapper
   autowire: true
```

For other just init new mapper object
```php
use Doctrine\Common\Annotations\AnnotationReader;

$mapper = new AnnotationMapper(new AnnotationReader(), $entityManager);
```

## Usage

At first you need to mark your setters in doctrine entity with [DTO](https://github.com/GitHubHubus/dto-annotation-mapper/blob/master/src/OK/Dto/Annotation/DTO.php) annotation
```php
/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $article;

    /**
     * @var Material
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Product\Options\Material")
     */
    protected $material;

    /**
     * @DTO(name="article", type="string")
     */
    public function setArticle(?string $article)
    {
        $this->article = $article;
    }

    /**
     * @DTO(name="material", type="App\Entity\Product\Options\Material", relation="ManyToOne")
     */
    public function setMaterial(?Material $material)
    {
        $this->material = $material;
    }
```

Then just pass input data with object to `fillObject` method in controller like this (Example for Symfony project, but you can use it with any framework or without it):

```php
    /**
     * @Route("/products/{id}", name="api_product_patch", methods={"PATCH"})
     */
    public function updateProductAction(Request $request, Product $product, MapperInterface $mapper): JsonResponse
    {
        $data = $this->getRequestData($request);

        $updatedProduct = $mapper->fillObject($product, $data);

        /* Do validation and other actions if need */

        $this->getDoctrine()->getManager()->flush();

        return $this->json($updatedProduct);
    }
```
That's it! Your entity will updated with new data.

Available simple types: string|float|bool|boolean|int|integer|datetime.

You can use any class as type and then you need to specify `relation`: ManyToOne|OneToMany|ManyToMany

## Testing

``` bash
composer install
vendor/bin/phpunit tests
```

## Information

You can use both `snake_case` and `camelCase` in `name` field and mapper will check both of variants in dataset if doesn't find the strict key. For example:
If you have annotation:
```php
@DTO(name="customerNumber", type="string")
```
and dataset:
```php
$data = ['customer_number'];
``` 

It's fine. At first mapper check the strict name `customerNumber`, then (if name doesn't exist) transforms name to `customer_number` and check it again.
It works other way too (from `snake_case` to `camelCase`).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

