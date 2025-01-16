<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryCourseLinkPlaylist")]
#[ORM\Table(name: "learn_course_link_playlist")]
class CourseLinkPlaylist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Course")]
    #[ORM\JoinColumn(name: "course_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $courseId;

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Playlist")]
    #[ORM\JoinColumn(name: "playlist_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private $playlistId;

    #[ORM\Column(type: "integer", name: "index_order")]
    private $indexOrder;

    public function getId()
    {
        return $this->id;
    }

    public function getCourseId()
    {
        return $this->courseId;
    }

    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;
    }

    public function getPlaylistId()
    {
        return $this->playlistId;
    }

    public function setPlaylistId($playlistId)
    {
        $this->playlistId = $playlistId;
    }

    public function getIndexOrder()
    {
        return $this->indexOrder;
    }

    public function setIndexOrder($indexOrder)
    {
        $this->indexOrder = $indexOrder;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'courseId' => $this->getCourseId(),
            'playlistId' => $this->getPlaylistId(),
            'indexOrder' => $this->getIndexOrder()
        ];
    }
}
