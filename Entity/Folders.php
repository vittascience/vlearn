<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryFolders")
 * @ORM\Table(name="learn_folders")
 */
class Folders implements \JsonSerializable, \Utils\JsonDeserializer
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=30, nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="Learn\Entity\Folders")
     * @ORM\JoinColumn(name="parent_folder", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Folders
    */
    private $parentFolder = null;


    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     * @var User
    */
    private $user;



    public function __construct($name, $user = null, $parentFolder = null)
    {
        $this->name = $name;
        $this->user = $user;
        $this->parentFolder = $parentFolder;
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return Folders
     */
    public function getParentFolder()
    {
        return $this->parentFolder;
    }

    /**
     * @param Folders $parentFoler
     */
    public function setParentFolder($parentFolder)
    {
        $this->parentFolder = $parentFolder;
    }


    public function jsonSerialize()
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
