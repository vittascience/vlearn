<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\FolderRepository")
 * @ORM\Table(name="classroom_folder")
 */
class Folder implements \JsonSerializable, \Utils\JsonDeserializer
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
     * @ORM\Column(name="background_image", type="string", length=255, nullable=true)
     * @var string
     */
    private $backgroundImg;


    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     * @var User
    */
    private $user;



    public function __construct($name, $backgroundImg = null)
    {
        $this->name = $name;
        $this->backgroundImg = $backgroundImg;
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
     * @return string
     */
    public function getBackgroundImg()
    {
        return $this->backgroundImg;
    }

    /**
     * @param string $backgroundImg
     */
    public function setBackgroundImg($backgroundImg)
    {
        $this->backgroundImg = $backgroundImg;
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


    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'backgroundImg' => $this->getBackgroundImg(),
            'user' => $this->getUser()
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
