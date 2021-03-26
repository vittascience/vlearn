<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryLesson")
 * @ORM\Table(name="learn_chapters_link_tutorials",
 *   uniqueConstraints={
 *       @ORM\UniqueConstraint(name="chapter_tutorial_unique", columns={"chapter_id", "tutorial_id"})
 *   }
 *  )
 */
class Lesson
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Chapter::class, inversedBy="lesson")
     */
    private $chapter;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="lesson")
     */
    private $tutorial;



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

    public function getChapter()
    {
        return $this->chapter;
    }

    public function setChapter($chapter)
    {
        if ($chapter instanceof Chapter || $chapter == null) {
            $this->chapter = $chapter;
        } else {
            throw new EntityDataIntegrityException("chapter attribute needs to be an instance of Chapter or null");
        }
    }

    public function getCourse()
    {
        return $this->tutorial;
    }

    public function setCourse($tutorial)
    {
        if ($tutorial instanceof Course || $tutorial == null) {
            $this->tutorial = $tutorial;
        } else {
            throw new EntityDataIntegrityException("tutorial attribute needs to be an instance of Course or null");
        }
    }
}
