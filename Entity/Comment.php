<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;
use Doctrine\Common\Collections\ArrayCollection;
use User\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryComment")
 * @ORM\Table(name="learn_comments")
 */
class Comment
{
    // REG_MESSAGE: Only letters and digits and length of string is between 1 and 1000
    const REG_MESSAGE = "/^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]{1}[\w\sáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ'&@-_()]{0,999}[\wáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ)]{0,1}$/";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="comment")
     */
    private $tutorial;
    
    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    private $user;
    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Comment")
     * @ORM\JoinColumn(name="comment_answered", referencedColumnName="id", onDelete="CASCADE")
     * @var Comment
     */
    private $commentAnswered = null;

    /**
     * @ORM\Column(type="string")
     */
    private $message;
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
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }
    public function __toString()
    {
        $format = "Course (id: %s, user: %s, message: %s, part: %s)\n";
        return sprintf($format, $this->id, $this->user, $this->message, $this->part);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and positive");
    }

    public function getTutorial()
    {
        return $this->tutorial;
    }

    public function setTutorial($tutorial)
    {
        if ($tutorial instanceof Course || $tutorial == null) {
            $this->tutorial = $tutorial;
        } else {
            throw new EntityDataIntegrityException("tutorial attribute needs to be an instance of Course or null");
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

    public function getCommentAnswered()
    {
        return $this->commentAnswered;
    }

    public function setCommentAnswered($commentAnswered)
    {
        if ($commentAnswered instanceof Comment || $commentAnswered == null) {
            $this->commentAnswered = $commentAnswered;
        } else {
            throw new EntityDataIntegrityException("commentAnswered attribute needs to be an instance of Comment or null");
        }
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        if (preg_match(self::REG_MESSAGE, $message)) {
            $this->message = $message;
        } else {
            throw new EntityDataIntegrityException("message needs to be string and have between 1 and 1000 characters");
        }
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        if ($createdAt instanceof \DateTime || $createdAt == null) {
            $this->createdAt = $createdAt;
        } else {
            throw new EntityDataIntegrityException("createdAt needs to be DateTime or null");
        }
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        if ($updatedAt instanceof \DateTime || $updatedAt == null) {
            $this->updatedAt = $updatedAt;
        } else {
            throw new EntityDataIntegrityException("updatedAt needs to be DateTime or null");
        }
    }
}
