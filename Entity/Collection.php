<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Utils\MetaDataMatcher;

/**
 * @ORM\Entity(repositoryClass="Learn\Repository\RepositoryCollection")
 * @ORM\Table(name="learn_collections")
 */
class Collection implements \JsonSerializable, \Utils\JsonDeserializer
{
    // REG_NAME_GRADE_COLLECTION: Only letters and digits and length of string is between 1 and 100
    const REG_NAME_GRADE_COLLECTION = "/^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]{1}[\w\sáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ'&@-_()]{0,99}[\wáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ)]{0,1}$/";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * 
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=Chapter::class, cascade={"persist", "remove"}, mappedBy="collection")
     */
    private $chapter;

    /**
     * @ORM\Column(name="name_collection", type="string", length=100, nullable=false, options={"default":"Unamed"})
     * @var string
     */
    private $nameCollection = "Unamed";
    /**
     * @ORM\Column(name="grade_collection", type="string", length=100, nullable=false, options={"default":"Unamed"})
     * @var string
     */
    private $gradeCollection = "Unamed";

    public function __construct($id = 0, $nameCollection = "Unamed", $gradeCollection = "Unamed")
    {
        $this->id = $id;
        $this->nameCollection = $nameCollection;
        $this->gradeCollection = $gradeCollection;
    }

    /**
     * @return int
     */
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

    /**
     * @return string
     */
    public function getNameCollection()
    {
        return $this->nameCollection;
    }

    /**
     * @param string $nameCollection
     */
    public function setNameCollection($nameCollection)
    {
        if (preg_match(self::REG_NAME_GRADE_COLLECTION, $nameCollection)) {
            $this->nameCollection = $nameCollection;
        } else {
            throw new EntityDataIntegrityException("nameCollection needs to be string and have between 1 and 100 characters");
        }
    }

    /**
     * @return string
     */
    public function getGradeCollection()
    {
        return $this->gradeCollection;
    }

    /**
     * @param string $gradeCollection
     */
    public function setGradeCollection($gradeCollection)
    {
        if (preg_match(self::REG_NAME_GRADE_COLLECTION, $gradeCollection)) {
            $this->gradeCollection = $gradeCollection;
        } else {
            throw new EntityDataIntegrityException("gradeCollection needs to be string and have between 1 and 100 characters");
        }
    }

    public function copy($objectToCopyFrom)
    {

        if ($objectToCopyFrom instanceof Collection) {

            $this->setNameCollection(urldecode($objectToCopyFrom->getNameCollection()));
            $this->setGradeCollection(urldecode($objectToCopyFrom->getGradeCollection()));
        } else {
            throw new EntityOperatorException("ObjectToCopyFrom attribute needs to be an instance of Collection");
        }
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'nameCollection' => $this->nameCollection,
            'gradeCollection' => $this->gradeCollection,
        ];
    }

    public static function jsonDeserialize($json)
    {
        $classInstance = new Collection();
        if (is_string($json))
            $json = json_decode($json);
        foreach ($json as $key => $value)
            $classInstance->{$key} = $value;
        return $classInstance;
    }
}
