<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryTag")]
#[ORM\Table(name: "learn_tags")]
class Tag implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Tag")]
    #[ORM\JoinColumn(name: "parent_tag", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Tag $parentTag = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getParentTag(): ?Tag
    {
        return $this->parentTag;
    }

    public function setParentTag(?Tag $parentTag): void
    {
        $this->parentTag = $parentTag;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'parentTag' => $this->getParentTag()
        ];
    }

    public static function jsonDeserialize($jsonDecoded): self
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
