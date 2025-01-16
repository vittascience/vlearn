<?php

namespace Learn\Entity;

use User\Entity\User;
use Learn\Entity\Folders;
use Utils\MetaDataMatcher;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryActivity")]
#[ORM\Table(name: "learn_activities")]
class Activity implements \JsonSerializable, \Utils\JsonDeserializer
{
    // REG_TITLE: Only letters and digits and length of string is between 1 and 1000
    const REG_TITLE = "/.{0,999}/";
    // REG_CONTENT: Only letters and digits and length of string is between 1 and 10000
    const REG_CONTENT = "/.{0,9999}/";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "is_from_classroom", type: "boolean", nullable: false, options: ["default" => false])]
    private $isFromClassroom = false;

    #[ORM\Column(name: "title", type: "string", length: 1000, nullable: true, options: ["default" => "No title"])]
    private $title = "No title";

    #[ORM\Column(name: "content", type: "string", length: 10000, nullable: false, options: ["default" => "No content"])]
    private $content = "no content";

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Activity")]
    #[ORM\JoinColumn(name: "id_fork", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private $fork = null;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "id_user", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private $user = null;

    #[ORM\Column(name: "type", type: "string", length: 255, nullable: true)]
    private $type;

    #[ORM\Column(name: "solution", type: "text", nullable: true)]
    private $solution;

    #[ORM\Column(name: "tolerance", type: "integer", length: 11, nullable: true)]
    private $tolerance;

    #[ORM\Column(name: "is_autocorrect", type: "boolean", nullable: true)]
    private $isAutocorrect;

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Folders")]
    #[ORM\JoinColumn(name: "folder", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private $folder;

    #[ORM\Column(name: "is_collapsed", type: "boolean", nullable: true, options: ["default" => 0])]
    private $isCollapsed = false;

    public function __construct($title, $content, $user = null, $isFromClassroom = false)
    {
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
        $this->isFromClassroom = $isFromClassroom;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        if (preg_match(self::REG_TITLE, $title)) {
            $this->title = $title;
        } else {
            throw new EntityDataIntegrityException("title needs to be string and have between 1 and 1000 characters");
        }
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        if (preg_match(self::REG_CONTENT, $content)) {
            $this->content = $content;
        } else {
            throw new EntityDataIntegrityException("content needs to be string and have between 1 and 10000 characters");
        }
    }

    public function getFork()
    {
        return $this->fork;
    }

    public function setFork($fork)
    {
        if ($fork instanceof Activity || $fork == null) {
            $this->fork = $fork;
        } else {
            throw new EntityDataIntegrityException("fork attribute needs to be an instance of Activity or null");
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        if ($user instanceof User || $user == null) {
            $this->user = $user;
        } else {
            throw new EntityDataIntegrityException("user attribute needs to be an instance of User or null");
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else {
            throw new EntityDataIntegrityException("id needs to be integer and positive");
        }
    }

    public function isFromClassroom()
    {
        return $this->isFromClassroom;
    }

    public function setisFromClassroom($isFromClassroom)
    {
        if ($isFromClassroom === null) {
            throw new EntityDataIntegrityException("isFromClassroom attribute should not be null");
        }
        $isFromClassroom = filter_var($isFromClassroom, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_bool($isFromClassroom)) {
            $this->isFromClassroom = $isFromClassroom;
        } else {
            throw new EntityDataIntegrityException("isFromClassroom needs to be a boolean");
        }
    }

    public function setType(?string $type): Activity
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(string $solution): Activity
    {
        $this->solution = $solution;
        return $this;
    }

    public function getTolerance(): ?int
    {
        return $this->tolerance;
    }

    public function setTolerance(int $tolerance): Activity
    {
        $this->tolerance = $tolerance;
        return $this;
    }

    public function getIsAutocorrect(): ?bool
    {
        return $this->isAutocorrect;
    }

    public function setIsAutocorrect(bool $isAutocorrect): Activity
    {
        $this->isAutocorrect = $isAutocorrect;
        return $this;
    }

    public function getFolder(): ?Folders
    {
        return $this->folder;
    }

    public function setFolder(?Folders $folder): Activity
    {
        $this->folder = $folder;
        return $this;
    }

    public function getIsCollapsed()
    {
        return $this->isCollapsed;
    }

    public function setIsCollapsed($isCollapsed)
    {
        if (!is_bool($isCollapsed)) {
            throw new EntityDataIntegrityException("isCollapsed has to be a boolean value");
        }
        $this->isCollapsed = $isCollapsed;
        return $this;
    }

    public function copy($objectToCopyFrom)
    {
        $this->setTitle($objectToCopyFrom->getTitle());
        $this->setContent($objectToCopyFrom->getContent());
        $this->setFork($objectToCopyFrom->getFork());
        $this->setUser($objectToCopyFrom->getUser());
        $this->setIsFromClassroom($objectToCopyFrom->isFromClassroom());
    }

    public function jsonSerialize()
    {
        $fork = $this->getFork() ? $this->getFork()->jsonSerialize() : null;
        $user = $this->getUser() ? $this->getUser()->jsonSerialize() : null;

        $unserialized = @unserialize($this->getContent());
        $unserializedSolution = @unserialize($this->getSolution());

        $content = $unserialized ? json_encode($unserialized) : $this->getContent();
        $solution = $unserializedSolution ? json_encode($unserializedSolution) : $this->getSolution();

        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'content' => $content,
            'isFromClassroom' => $this->isFromClassroom(),
            'user' => $user,
            "fork" => $fork,
            "type" => $this->getType(),
            "solution" => $solution,
            "tolerance" => $this->getTolerance(),
            "isAutocorrect" => $this->getIsAutocorrect(),
            "folder" => $this->getFolder(),
            'isCollapsed' => $this->getIsCollapsed()
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self("title", "content", 77);
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $attributeType = MetaDataMatcher::matchAttributeType(self::class, $attributeName);
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
