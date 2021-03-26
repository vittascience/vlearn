<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;
use User\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryFavorite")
 * @ORM\Table(name="learn_favorites",
 *   uniqueConstraints={
 *       @ORM\UniqueConstraint(name="user_tutorial_unique", columns={"user_id", "tutorial_id"})
 *   }
 *  )
 */
class Favorite
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User\Entity\User", inversedBy="favorite")
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="favorite")
     */
    private $tutorial;

    public function __toString()
    {
        $format = "Participation (Id: %s, %s, %s)\n";
        return sprintf($format, $this->id, $this->user, $this->poll);
    }
    public function __construct($user, $tutorial)
    {
        $this->user = $user;
        $this->tutorial = $tutorial;
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
}
