<?php

namespace Learn\Entity;

use Learn\Entity\Tag;
use Utils\MetaDataMatcher;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityOperatorException;
use Utils\Exceptions\EntityDataIntegrityException;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryActivityLinkTag")
 * @ORM\Table(name="learn_activities_link_tags" )
 */
class ActivityLinkTag implements \JsonSerializable, \Utils\JsonDeserializer
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Activity")
     * @ORM\JoinColumn(name="id_activity", referencedColumnName="id", onDelete="CASCADE")
     */
    private $activity;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Tag")
     * @ORM\JoinColumn(name="id_tag", referencedColumnName="id", onDelete="CASCADE")
     */
    private $tag;



    public function __construct(Activity $activity, Tag $tag)
    {
        $this->setTag($tag);
        $this->setActivity($activity);
    }

    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag($tag)
    {
        if ($tag instanceof Tag) {
            $this->tag = $tag;
        } else {
            throw new EntityDataIntegrityException("course attribute needs to be an instance of Tag");
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


    public function jsonSerialize()
    {
        return [
            "tag" => $this->getTag(),
            "activity" => $this->getActivity(),
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
