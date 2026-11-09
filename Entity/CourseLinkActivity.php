<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;

#[ORM\Table(name: 'learn_courses_link_activities')]
#[ORM\Entity(repositoryClass: \Learn\Repository\RepositoryCourseLinkActivity::class)]
class CourseLinkActivity implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\JoinColumn(name: 'id_course', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Learn\Entity\Course::class)]
    private $course;

    #[ORM\JoinColumn(name: 'id_activity', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Learn\Entity\Activity::class)]
    private $activity;
    #[ORM\Column(type: 'integer', name: 'index_order')]
    private $indexOrder;


    public function __construct(Course $course, Activity $activity, $indexOrder)
    {
        $this->setCourse($course);
        $this->setActivity($activity);
        $this->setIndexOrder($indexOrder);
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     */
    public function setCourse($course)
    {
        if ($course instanceof Course) {
            $this->course = $course;
        } else {
            throw new EntityDataIntegrityException("course attribute needs to be an instance of Course");
        }
    }

    /**
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity $activity
     */
    public function setActivity($activity)
    {
        if ($activity instanceof Activity) {
            $this->activity = $activity;
        } else {
            throw new EntityDataIntegrityException("activity attribute needs to be an instance of Activity");
        }
    }

    /**
     * @return int
     */
    public function getIndexOrder()
    {
        return $this->indexOrder;
    }

    /**
     * @param int $indexOrder
     */
    public function setIndexOrder($indexOrder)
    {
        $indexOrder = intval($indexOrder);
        if (is_int($indexOrder)) {
            $this->indexOrder = $indexOrder;
        } else {
            throw new EntityDataIntegrityException("indexOrder needs to be integer ");
        }
    }


    public function jsonSerialize()
    {
        return [
            "course" => $this->getCourse(),
            "activity" => $this->getActivity(),
            "indexOrder" => $this->getIndexOrder()
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
