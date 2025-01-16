<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\MetaDataMatcher;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryFolders")]
#[ORM\Table(name: "learn_folders")]
class Folders implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 30, nullable: false)]
    private string $name;

    #[ORM\OneToOne(targetEntity: "Learn\Entity\Folders")]
    #[ORM\JoinColumn(name: "parent_folder", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Folders $parentFolder = null;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user", nullable: false, referencedColumnName: "id", onDelete: "CASCADE")]
    private $user;

    public function __construct(string $name, $user = null, ?Folders $parentFolder = null)
    {
        $this->name = $name;
        $this->user = $user;
        $this->parentFolder = $parentFolder;
    }

    public function getId()
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

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getParentFolder(): ?Folders
    {
        return $this->parentFolder;
    }

    public function setParentFolder(?Folders $parentFolder): void
    {
        $this->parentFolder = $parentFolder;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'user' => $this->getUser(),
            'parentFolder' => $this->getParentFolder(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self("title", "content", 77);
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $attributeType = MetaDataMatcher::matchAttributeType(
                self::class,
                $attributeName
            );
            if ($attributeType instanceof \DateTime) {
                $date = new \DateTime();
                $date->setTimestamp($attributeValue);
                $attributeValue = $date;
            }
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
