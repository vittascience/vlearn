<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryTag")
 * @ORM\Table(name="learn_tags")
 */
class Tag implements \JsonSerializable, \Utils\JsonDeserializer
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @var string
     */
    private $name;


    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Tag")
     * @ORM\JoinColumn(name="parent_tag", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Tag
     */
    private $parentTag;


    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(String $name)
    {
        $this->name = $name;
    }

    /**
     * @return Tag
     */
    public function getParentTag()
    {
        return $this->parentTag;
    }

    /**
     * @param Tag $parentTag
     */
    public function setParentTag(?Tag $parentTag)
    {
        $this->parentTag = $parentTag;
    }



    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'parentTag' => $this->getParentTag()
        ];
    }


    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
