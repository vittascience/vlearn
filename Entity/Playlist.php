<?php

namespace Learn\Entity;

use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryPlaylist")]
#[ORM\Table(name: "learn_playlist")]
class Playlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "title", type: "string", length: 1000, nullable: false)]
    private string $title;

    #[ORM\Column(name: "description", type: "string", length: 1000, nullable: true)]
    private ?string $description;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user", nullable: false, referencedColumnName: "id", onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(name: "created_at", type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTime $createdAt;

    #[ORM\Column(name: "updated_at", type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTime $updatedAt;

    #[ORM\Column(name: "rights", type: "integer", nullable: false, options: ["default" => 0])]
    private int $rights;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
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
        $user = $this->getUser() ? $this->getUser()->jsonSerialize() : null;

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
