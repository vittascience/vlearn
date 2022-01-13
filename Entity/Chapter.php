<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryChapter")
 * @ORM\Table(name="learn_chapters",
 *   uniqueConstraints={
 *       @ORM\UniqueConstraint(name="collection_name_unique", columns={"collection_id", "name"})
 *   }
 *  )
 */
class Chapter
{
    // REG_NAME: Only letters and digits and length of string is between 1 and 100
    const REG_NAME = "/^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]{1}[\w\sáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ'&@-_()]{0,99}[\wáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ)]{0,1}$/";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity=Collection::class, inversedBy="chapter")
     */
    private $collection;
    /**
     * @ORM\OneToMany(targetEntity=Lesson::class, mappedBy="chapter")
     */
    private $lesson;
    /**
     * @ORM\Column(type="string")
     */
    private $name;

    public function __construct($id = 0, $name = "")
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function __toString()
    {
        $format = "Chapter (id: %s, collection: %s, lesson: %s, name: %s)\n";
        return sprintf($format, $this->getId(), $this->getCollection(), $this->lesson, $this->name);
    }
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and positive");
    }
    public function getCollection()
    {
        return $this->collection;
    }
    /**
     * @param Collection $collection
     */
    public function setCollection($collection)
    {
        if ($collection instanceof Collection || $collection == null) {
            $this->collection = $collection;
        } else {
            throw new EntityDataIntegrityException("collection attribute needs to be an instance of collection or null");
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        if (preg_match(self::REG_NAME, $name)) {
            $this->name = $name;
        } else {
            throw new EntityDataIntegrityException("name needs to be string and have between 1 and 100 characters");
        }
    }

    public function getLesson()
    {
        return $this->lesson;
    }
    public function setLesson($lesson)
    {
        if ($lesson instanceof Lesson || $lesson == null) {
            $this->lesson = $lesson;
        } else {
            throw new EntityDataIntegrityException("lesson attribute needs to be an instance of Lesson or null");
        }
    }
}
