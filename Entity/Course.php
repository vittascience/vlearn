<?php

namespace Learn\Entity;

use User\Entity\User;
use Learn\Entity\Comment;
use Learn\Entity\Folders;
use Utils\MetaDataMatcher;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityOperatorException;
use Doctrine\Common\Collections\ArrayCollection;
use Utils\Exceptions\EntityDataIntegrityException;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryCourse")
 * @ORM\Table(name="learn_courses")
 */
class Course implements \JsonSerializable, \Utils\JsonDeserializer
{
    const TITLE_MAX_LENGTH = 255;
    const TIME_MIN = 1;
    const TITLE_PART_MAX_LENGTH = 255;
    const PRODUCT_MAX_LENGTH = 100;
    const MAX_PICTURE_SIZE = 10000000;
    // REG_TITLE: Only letters and digits and length of string is between 1 and 1000

    const REG_TITLE = "/.{1,1000}/";
    // REG_DESC: Only letters and digits and length of string is between 1 and 1000
    //const REG_DESC = "/.,;{1,1000}/";

    // REG_LANG: Only  a-z letters length of string is equal 2
    const REG_LANG = "/^[a-z]{2}$/";
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    public $activities = [];
    /**
     * @ORM\OneToMany(targetEntity=Favorite::class, mappedBy="tutorial")
     */

    private $favorite;

    /**
     * @ORM\OneToMany(targetEntity=Lesson::class, mappedBy="tutorial")
     */
    private $lesson;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="tutorial")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    private $user = null;

    /**
     * @ORM\Column(name="title", type="string", length=1000, nullable=false, options={"default":"No title"})
     * @var string
     */
    private $title = "No title";
    /**
     * @ORM\Column(name="description", type="string", length=1000, nullable=false, options={"default":"No description"})
     * @var string
     */
    private $description = "No description";
    /**
     * @ORM\Column(name="duration", type="integer", options={"default":3600})
     * @var integer
     */
    private $duration = 3600; // in seconds

    /**
     * @ORM\Column(name="views", type="integer", options={"default":0})
     * @var integer
     */
    private $views = 0; // in seconds
    /**
     * @ORM\Column(name="difficulty", type="integer", nullable=false, options={"default":0})
     * @var integer
     * difficulty is between 0 and 3
     */
    private $difficulty = 0;
    /**
     * @ORM\Column(name="lang", type="string", length=100, nullable=true, options={"default":"No lang"})
     * @var string
     */
    private $lang = "No lang";
    /**
     * @ORM\Column(name="support", type="integer", nullable=true, options={"default":0})
     * @var integer
     */
    private $support;
    /**
     * @ORM\Column(name="img", type="string", length=10000, nullable=false, options={"default":"No image"})
     * @var string
     */
    private $img = "No image";
    /**
     * @ORM\Column(name="link", type="string", nullable=false, options={"default":"No link"})
     * @var string
     */
    private $link = "no link";
    /**
     * @ORM\Column(name="created_at", type="datetime", columnDefinition="TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @ORM\Column(name="updated_at", type="datetime", columnDefinition="TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
     * @var \DateTime
     */
    private $updatedAt;
    /**
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false, options={"default":false})
     * @var bool
     */
    private $deleted = false;

    /**
     * @ORM\Column(name="rights", type="integer", nullable=false, options={"default":0})
     * @var integer
     * values are between 0 and 3
     */
    private $rights = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Course")
     * @ORM\JoinColumn(name="id_fork", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Course
     */
    private $fork = null;


    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Folders")
     * @ORM\JoinColumn(name="folder", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     * @var Folders
     */
    private $folder;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Course constructor
     */
    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
        $this->setLink(uniqid());
        $this->setImg("basic.png");
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

    public function getFork()
    {
        return $this->fork;
    }
    /**
     * @param Course $fork
     */
    public function setFork($fork)
    {
        if ($fork instanceof Course || $fork == null) {
            $this->fork = $fork;
        } else {
            throw new EntityDataIntegrityException("fork attribute needs to be an instance of Course or null");
        }
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
        if ($user instanceof User || $user == null) {
            $this->user = $user;
        } else {
            throw new EntityDataIntegrityException("user attribute needs to be an instance of User or null");
        }
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

            throw new EntityDataIntegrityException("title needs to be string and have between 1 and 1000 characters : " . $title);
        }
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(String $description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $duration = intval($duration);
        if (is_int($duration) && $duration >= 0) {
            $this->duration = $duration;
        } else {
            throw new EntityDataIntegrityException("duration needs to be integer and positive");
        }
    }

    /**
     * @return int
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param int $views
     */
    public function incrementViews()
    {

        $this->views += 1;
    }

    /**
     * @param int $views
     */
    public function setViews($views)
    {
        $views = intval($views);
        if (is_int($views) && $views >= 0) {
            $this->views = $views;
        } else {
            throw new EntityDataIntegrityException("views needs to be integer and positive");
        }
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * @param int $difficulty
     */
    public function setDifficulty($difficulty)
    {
        $difficulty = intval($difficulty);
        if (is_int($difficulty) && $difficulty >= 0) {
            $this->difficulty = $difficulty;
        } else {
            throw new EntityDataIntegrityException("difficulty needs to be integer and positive");
        }
    }
    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     */
    public function setLang(String $lang)
    {
        $this->lang = $lang;
    }

    /**
     * @return int
     */
    public function getSupport()
    {
        return $this->support;
    }

    /**
     * @param int $support
     */
    public function setSupport(Int $support)
    {
        $this->support = $support;
    }

    /**
     * @return string
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param string $img
     */
    public function setImg($img)
    {

        if ($img != 'basic.png') {
            $arrayPicture = $this->checkPicture($img);
            if (!$arrayPicture['success']) {
            }
            $tmpPath = $img['tmp_name'];
            $realPath = __DIR__ . "/../../../../public/content/user_data/tuto_img/";
            while (true) {
                $filename = uniqid(rand(), true) . '_' . $img['name'];
                if (!file_exists($realPath . "" . $filename)) break;
            }
           
            move_uploaded_file($tmpPath, $realPath . "" . $filename);
            //création d'une image de poids réduite
            /* resize_img($realPath . $filename, $realPath . 'lazy_' . $filename); */
        } else {
            $filename = $img;
        }
        $this->img = $filename;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {

        $this->link = $link;
    }
    /**
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime;
    }

    /**
     * @return datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime;
    }

    /**
     * @return int
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param mixed $deleted
     */
    public function setDeleted($deleted)
    {
        if ($deleted === null) {
            throw new EntityDataIntegrityException("deleted attribute should not be null");
        }
        $deleted = filter_var($deleted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_bool($deleted)) {
            $this->deleted = $deleted;
        } else {
            throw new EntityDataIntegrityException("deleted needs to be a boolean");
        }
    }

    /**
     * @return int
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @param int $rights
     */
    public function setRights($rights)
    {
        $rights = intval($rights);
        if (is_int($rights) && ($rights >= 0 && $rights <= 3)) {
            $this->rights = $rights;
        } else {
            throw new EntityDataIntegrityException("rights needs to be integer and between 0 and 3");
        }
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
    public function setFolder(?Folders $folder): Course
    {
        $this->folder = $folder;
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
        } else {
            throw new EntityOperatorException("ObjectToCopyFrom attribute needs to be an instance of Course");
        }
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
            'folder' => $this->getFolder()
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
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
            if ($attributeName == 'activities') {
                echo ('--a--');
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


    public static function checkTitle($title)
    {
        if (strlen($title) > self::TITLE_MAX_LENGTH || strlen($title) < 1)
            return false;
        return true;
    }
    public static function unsetImage($object)
    {
        unset($object->img);
    }

    public static function checkTime($time)
    {
        if ($time < self::TIME_MIN)
            return false;
        return true;
    }

    public static function checkProductTitle($title)
    {
        if (strlen($title) > self::PRODUCT_MAX_LENGTH)
            return false;
        return true;
    }

    public static function checkPicture($picture)
    {
        if ($picture != 'basic.png') {
            $arrayData = [];
            if (preg_match("/image\/png/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'png';
            } else if (preg_match("/image\/jpeg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpeg';
            } else if (preg_match("/image\/jpg/", $picture["type"])) {
                $arrayData['success'] = true;
                $arrayData['ext'] = 'jpg';
            } else {
                $arrayData['success'] = false;
            }
            if ($picture["size"] > self::MAX_PICTURE_SIZE)
                $arrayData['success'] = false;
            list($width, $height) = getimagesize($picture["tmp_name"]);
            if ($width < 300)
                $arrayData['success'] = false;
            $ratio = $width / $height;
            if ($ratio < 1 || $ratio > 3)
                $arrayData['success'] = false;
            return $arrayData;
        }
    }
}
