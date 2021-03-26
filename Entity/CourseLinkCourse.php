<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryCourseLinkCourse")
 * @ORM\Table(name="learn_tutorials_link_tutorials",
 *   uniqueConstraints={
 *       @ORM\UniqueConstraint(name="couple_tutorial_unique", columns={"tutorial1_id", "tutorial2_id"})
 *   }
 *  )
 */
class CourseLinkCourse
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="related")
     */
    private $tutorial1;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="related")
     */
    private $tutorial2;

    public function __construct($tutorial1, $tutorial2)
    {
        $this->tutorial1 = $tutorial1;
        $this->tutorial2 = $tutorial2;
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

    public function getCourse1()
    {
        return $this->tutorial1;
    }

    public function setCourse1($tutorial1)
    {
        if ($tutorial1 instanceof Course || $tutorial1 == null) {
            $this->tutorial1 = $tutorial1;
        } else {
            throw new EntityDataIntegrityException("tutorial attribute needs to be an instance of Course or null");
        }
    }

    public function getCourse2()
    {
        return $this->tutorial2;
    }

    public function setCourse2($tutorial2)
    {
        if ($tutorial2 instanceof Course || $tutorial2 == null) {
            $this->tutorial2 = $tutorial2;
        } else {
            throw new EntityDataIntegrityException("tutorial attribute needs to be an instance of Course or null");
        }
    }
}
