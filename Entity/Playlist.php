<?php

namespace Learn\Entity;

use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryPlaylist")
 * @ORM\Table(name="learn_playlist")
 */
class Playlist
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=1000, nullable=false)
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    private $user;

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
     * @ORM\Column(name="rights", type="integer", nullable=false, options={"default":0})
     * @var integer
     * values are between 0 and 3
     */
    private $rights;

    public function __construct($id, $title)
    {
        $this->id = $id;
        $this->title = $title;
    }

    
    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): String
    {
        return $this->title;
    }

    public function setTitle(String $title)
    {
        $this->title = $title;
    }

    public function getDescription(): ?String
    {
        return $this->description;
    }

    public function setDescription(?String $description)
    {
        $this->description = $description;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getRights(): int
    {
        return $this->rights;
    }

    public function setRights(int $rights)
    {
        $this->rights = $rights;
    }

    public function jsonSerialize()
    {

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
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt(),
            'rights' => $this->getRights(),
        ];
    }
}
