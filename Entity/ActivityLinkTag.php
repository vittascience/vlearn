<?php

namespace Learn\Entity;

use Learn\Entity\Tag;
use Learn\Entity\Activity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryActivityLinkTag")]
#[ORM\Table(name: "learn_activities_link_tags")]
class ActivityLinkTag implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Activity", inversedBy: "activityLinkTags")]
    #[ORM\JoinColumn(name: "id_activity", referencedColumnName: "id", onDelete: "CASCADE")]
    private $activity;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Tag", inversedBy: "activityLinkTags")]
    #[ORM\JoinColumn(name: "id_tag", referencedColumnName: "id", onDelete: "CASCADE")]
    private $tag;

    public function __construct(Activity $activity, Tag $tag)
    {
        $this->setActivity($activity);
        $this->setTag($tag);
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
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
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
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function jsonSerialize()
    {
        return [
            "tag" => $this->getTag()->jsonSerialize(),
            "activity" => $this->getActivity()->jsonSerialize(),
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
