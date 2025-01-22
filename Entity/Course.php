<?php

namespace Learn\Entity;

use User\Entity\User;
use Learn\Entity\Folders;
use Utils\MetaDataMatcher;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityOperatorException;
use Doctrine\Common\Collections\ArrayCollection;
use Utils\Exceptions\EntityDataIntegrityException;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryCourse")]
#[ORM\Table(name: "learn_courses")]
class Course implements \JsonSerializable, \Utils\JsonDeserializer
{
    const TITLE_MAX_LENGTH = 255;
    const TIME_MIN = 1;
    const TITLE_PART_MAX_LENGTH = 255;
    const PRODUCT_MAX_LENGTH = 100;
    const MAX_PICTURE_SIZE = 10000000;
    const REG_TITLE = "/.{1,1000}/";
    const REG_LANG = "/^[a-z]{2}$/";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    public $activities = [];

    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: "tutorial")]
    private $favorite;

    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: "tutorial")]
    private $lesson;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: "tutorial")]
    private $comment;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user", nullable: false, referencedColumnName: "id", onDelete: "CASCADE")]
    private $user = null;

    #[ORM\Column(name: "title", type: "string", length: 1000, nullable: false, options: ["default" => "No title"])]
    private $title = "No title";

    #[ORM\Column(name: "description", type: "string", length: 1000, nullable: false, options: ["default" => "No description"])]
    private $description = "No description";

    #[ORM\Column(name: "duration", type: "integer", options: ["default" => 3600])]
    private $duration = 3600;

    #[ORM\Column(name: "views", type: "integer", options: ["default" => 0])]
    private $views = 0;

    #[ORM\Column(name: "difficulty", type: "integer", nullable: false, options: ["default" => 0])]
    private $difficulty = 0;

    #[ORM\Column(name: "lang", type: "string", length: 100, nullable: true, options: ["default" => "No lang"])]
    private $lang = "No lang";

    #[ORM\Column(name: "support", type: "integer", nullable: true, options: ["default" => 0])]
    private $support;

    #[ORM\Column(name: "img", type: "string", length: 10000, nullable: false, options: ["default" => "No image"])]
    private $img = "No image";

    #[ORM\Column(name: "link", type: "string", nullable: false, options: ["default" => "No link"])]
    private $link = "no link";

    #[ORM\Column(name: "created_at", type: "datetime", columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private $createdAt;

    #[ORM\Column(name: "updated_at", type: "datetime", columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private $updatedAt;

    #[ORM\Column(name: "is_deleted", type: "boolean", nullable: false, options: ["default" => false])]
    private $deleted = false;

    #[ORM\Column(name: "rights", type: "integer", nullable: false, options: ["default" => 0])]
    private $rights = 0;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: "id_fork", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private $fork = null;

    #[ORM\ManyToOne(targetEntity: Folders::class)]
    #[ORM\JoinColumn(name: "folder", nullable: true, referencedColumnName: "id", onDelete: "CASCADE")]
    private $folder;

    #[ORM\Column(name: "format", type: "boolean", nullable: true, options: ["default" => false])]
    private $format;

    #[ORM\Column(name: "optional_data", type: "text", nullable: true)]
    private $optionalData = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
        $this->setLink(uniqid());
        $this->setImg("basic.png");
    }

    public function setId($id)
    {
        if ($id > 0) {
            $this->id = $id;
        } else {
            throw new EntityDataIntegrityException("id needs to be integer and positive");
        }
    }

    public function getFork(): ?Course
    {
        return $this->fork;
    }

    public function setFork(?Course $fork)
    {
        $this->fork = $fork;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        if (preg_match(self::REG_TITLE, $title)) {
            $this->title = $title;
        } else {
            throw new EntityDataIntegrityException("title needs to be string and have between 1 and 1000 characters : " . $title);
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        if ($duration >= 0) {
            $this->duration = $duration;
        } else {
            throw new EntityDataIntegrityException("duration needs to be integer and positive");
        }
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function incrementViews()
    {
        $this->views += 1;
    }

    public function setViews($views)
    {
        if ($views >= 0) {
            $this->views = $views;
        } else {
            throw new EntityDataIntegrityException("views needs to be integer and positive");
        }
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function setDifficulty($difficulty)
    {
        if ($difficulty >= 0) {
            $this->difficulty = $difficulty;
        } else {
            throw new EntityDataIntegrityException("difficulty needs to be integer and positive");
        }
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang)
    {
        $this->lang = $lang;
    }

    public function getSupport(): int
    {
        return $this->support;
    }

    public function setSupport(int $support)
    {
        $this->support = $support;
    }

    public function getImg(): string
    {
        return $this->img;
    }

    public function setImg($img)
    {
        if ($img != 'basic.png') {
            $arrayPicture = $this->checkPicture($img);
            if (!$arrayPicture['success']) {
                throw new EntityDataIntegrityException("Invalid picture");
            }
            $tmpPath = $img['tmp_name'];
            $realPath = __DIR__ . "/../../../../public/content/user_data/tuto_img/";
            while (true) {
                $filename = uniqid(rand(), true) . '_' . $img['name'];
                if (!file_exists($realPath . "" . $filename)) break;
            }
            move_uploaded_file($tmpPath, $realPath . "" . $filename);
        } else {
            $filename = $img;
        }
        $this->img = $filename;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link)
    {
        $this->link = $link;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;
    }

    public function getRights(): int
    {
        return $this->rights;
    }

    public function setRights($rights)
    {
        if ($rights >= 0 && $rights <= 3) {
            $this->rights = $rights;
        } else {
            throw new EntityDataIntegrityException("rights needs to be integer and between 0 and 3");
        }
    }

    public function getFolder(): ?Folders
    {
        return $this->folder;
    }

    public function setFolder(?Folders $folder): Course
    {
        $this->folder = $folder;
        return $this;
    }

    public function getFormat(): ?bool
    {
        return $this->format;
    }

    public function setFormat(?bool $format): Course
    {
        $this->format = $format;
        return $this;
    }

    public function getOptionalData(): ?string
    {
        return $this->optionalData;
    }

    public function setOptionalData(?string $optionalData): Course
    {
        $this->optionalData = $optionalData;
        return $this;
    }

    public function copy($objectToCopyFrom)
    {
        if ($objectToCopyFrom instanceof self) {
            $this->setTitle(urldecode($objectToCopyFrom->getTitle()));
            $this->setDescription(urldecode($objectToCopyFrom->getDescription()));
            $this->setDuration($objectToCopyFrom->getDuration());
            $this->setViews($objectToCopyFrom->getViews());
            $this->setDifficulty($objectToCopyFrom->getDifficulty());
            $this->setLang($objectToCopyFrom->getLang());
            $this->setSupport($objectToCopyFrom->getSupport());
            $this->setFork($objectToCopyFrom->getFork());

            if ($objectToCopyFrom->getImg() != "" && $objectToCopyFrom->getImg() != "basic.png") {
                $this->img = $objectToCopyFrom->getImg();
            }
            $this->setLink($objectToCopyFrom->getLink());
            $this->setUpdatedAt();
            $this->setRights($objectToCopyFrom->getRights());

            $this->setFolder($objectToCopyFrom->getFolder());
            $this->setFormat($objectToCopyFrom->getFormat());
            $this->setOptionalData($objectToCopyFrom->getOptionalData());
        } else {
            throw new EntityOperatorException("ObjectToCopyFrom attribute needs to be an instance of Course");
        }
    }

    public function jsonSerialize()
    {
        $fork = $this->getFork() ? $this->getFork()->jsonSerialize() : null;
        $user = $this->getUser() ? $this->getUser()->jsonSerialize() : null;
        $optionalData = $this->getOptionalData() ? json_decode($this->getOptionalData()) : null;

        return [
            'id' => $this->getId(),
            'user' => $user,
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'duration' => $this->getDuration(),
            'views' => $this->getViews(),
            'difficulty' => $this->getDifficulty(),
            'lang' => $this->getLang(),
            'support' => $this->getSupport(),
            'img' => $this->getImg(),
            'link' => $this->getLink(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
            'rights' => $this->getRights(),
            'fork' => $fork,
            'folder' => $this->getFolder(),
            'format' => $this->getFormat(),
            'optionalData' => $optionalData,
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $attributeType = MetaDataMatcher::matchAttributeType(self::class, $attributeName);
            if ($attributeType instanceof \DateTime) {
                $date = new \DateTime();
                $date->setTimestamp($attributeValue);
                $attributeValue = $date;
            }
            if ($attributeName == 'activities') {
                $array = new ArrayCollection();
                foreach ($attributeValue as $val) {
                    $array[] = Activity::jsonDeserialize($val);
                }
                $attributeValue = $array;
            }
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }

    public static function checkTitle($title): bool
    {
        return strlen($title) <= self::TITLE_MAX_LENGTH && strlen($title) >= 1;
    }

    public static function unsetImage($object)
    {
        unset($object->img);
    }

    public static function checkTime($time): bool
    {
        return $time >= self::TIME_MIN;
    }

    public static function checkProductTitle($title): bool
    {
        return strlen($title) <= self::PRODUCT_MAX_LENGTH;
    }

    public static function checkPicture($picture): array
    {
        if ($picture != 'basic.png') {
            $arrayData = [];
            if (preg_match("/image\/png/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'png';
            } elseif (preg_match("/image\/jpeg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpeg';
            } elseif (preg_match("/image\/jpg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpg';
            } else {
                $arrayData['success'] = false;
            }
            if ($picture["size"] > self::MAX_PICTURE_SIZE) {
                $arrayData['success'] = false;
            }
            list($width, $height) = getimagesize($picture["tmp_name"]);
            if ($width < 300) {
                $arrayData['success'] = false;
            }
            $ratio = $width / $height;
            if ($ratio < 1 || $ratio > 3) {
                $arrayData['success'] = false;
            }
            return $arrayData;
        }
    }
}
