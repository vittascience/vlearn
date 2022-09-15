<?php

namespace Learn\Entity;

use User\Entity\User;
use Learn\Entity\Folders;
use Utils\MetaDataMatcher;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityOperatorException;
use Utils\Exceptions\EntityDataIntegrityException;


/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryActivity")
 * @ORM\Table(name="learn_activities")
 */
class Activity implements \JsonSerializable, \Utils\JsonDeserializer
{
    // REG_TITLE: Only letters and digits and length of string is between 1 and 1000
    const REG_TITLE = "/.{0,999}/";
    // REG_CONTENT: Only letters and digits and length of string is between 1 and 10000
    const REG_CONTENT = "/.{0,9999}/";


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="is_from_classroom", type="boolean", nullable=false, options={"default":false})
     * @var bool
     */
    private $isFromClassroom = false;

    /**
     * @ORM\Column(name="title", type="string", length=1000, nullable=true, options={"default":"No title"})
     * @var string
     */
    private $title = "No title";

    /**
     * @ORM\Column(name="content", type="string", length=10000, nullable=false, options={"default":"No content"})
     * @var string
     */
    private $content = "no content";

    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Activity")
     * @ORM\JoinColumn(name="id_fork", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Activity
     */
    private $fork = null;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="id_user", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    private $user = null;

    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     * @var string
     */
    private $type;


    /**
     * @ORM\Column(name="solution", type="text", nullable=true)
     * @var String
     */
    private $solution;

    /**
     * @ORM\Column(name="tolerance", type="integer", length=11, nullable=true)
     * @var int
     */
    private $tolerance;


    /**
     * @ORM\Column(name="is_autocorrect", type="boolean", nullable=true)
     * @var bool
     */
    private $isAutocorrect;

    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Folders")
     * @ORM\JoinColumn(name="folder", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Folders
     */
    private $folder;

    /**
     * @ORM\Column(name="is_collapsed", type="boolean", nullable=true, options={"default":0})
     *
     * @var bool
     */
    private $isCollapsed = false;

    public function __construct($title, $content, $user = null, $isFromClassroom = false)
    {
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
        $this->isFromClassroom = $isFromClassroom;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        if (preg_match(self::REG_TITLE, $title)) {
            $this->title = $title;
        } else {
            throw new EntityDataIntegrityException("title needs to be string and have between 1 and 1000 characters");
        }
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
    /**
     * @param Activity $fork
     */
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
    /**
     * @param User $user
     */
    public function setUser($user)
    {
        if ($user instanceof User || $user == null) {
            $this->user = $user;
        } else {
            throw new EntityDataIntegrityException("user attribute needs to be an instance of User or null");
        }
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and positive");
    }

    /**
     * @return bool
     */
    public function isFromClassroom()
    {
        return $this->isFromClassroom;
    }

    /**
     * @param bool $isFromClassroom
     */
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

    /**
     * @var String $type
     * @return Object Activity
     */
    public function setType(?String $type): Activity
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return String type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return String type
     */
    public function getSolution(): ?string
    {
        return $this->solution;
    }

    /**
     * @param String $solution
     * @return Activity
     */
    public function setSolution(String $solution): Activity
    {
        $this->solution = $solution;
        return $this;
    }

    /**
     * @return int
     */
    public function getTolerance(): ?int
    {
        return $this->tolerance;
    }

    /**
     * @param int $tolerance
     * @return Activity
     */
    public function setTolerance(int $tolerance): Activity
    {
        $this->tolerance = $tolerance;
        return $this;
    }


    /**
     * @return bool
     */
    public function getIsAutocorrect(): ?bool
    {
        return $this->isAutocorrect;
    }

    /**
     * @param bool $isAutocorrect
     * @return Activity
     */
    public function setIsAutocorrect(bool $isAutocorrect): Activity
    {
        $this->isAutocorrect = $isAutocorrect;
        return $this;
    }


    /**
     * @return Folder
     */
    public function getFolder(): ?Folders
    {
        return $this->folder;
    }

    /**
     * @param Folder $folders
     * @return Activity
     */
    public function setFolder(?Folders $folder): Activity
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * Get the value of isCollapsed
     *
     * @return  bool
     */ 
    public function getIsCollapsed()
    {
        return $this->isCollapsed;
    }

    /**
     * Set the value of isCollapsed
     *
     * @param  bool  $isCollapsed
     *
     * @return  self
     */ 
    public function setIsCollapsed($isCollapsed)
    {
        if(!is_bool($isCollapsed)){
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
        if ($this->getFork() != null) {
            $fork = $this->getFork()->jsonSerialize();
        } else {
            $fork = null;
        }
        if ($this->getUser() != null) {
            $user = $this->getUser()->jsonSerialize();
        } else {
            $user = null;
        }

        $unserialized = @unserialize($this->getContent());
        $unserializedSolution = @unserialize($this->getSolution());
        // Handle the previous format
        if ($unserialized) {
            $content = json_encode($unserialized);
        } else {
            $content = $this->getContent();
        }

        if ($unserializedSolution) {
            $solution = json_encode($unserializedSolution);
        } else {
            $solution = $this->getSolution();
        }

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
