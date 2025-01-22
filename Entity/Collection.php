<?php

namespace Learn\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

#[ORM\Entity(repositoryClass: "Learn\Repository\RepositoryCollection")]
#[ORM\Table(name: "learn_collections")]
class Collection implements \JsonSerializable, \Utils\JsonDeserializer
{
    // REG_NAME_GRADE_COLLECTION: Only letters and digits and length of string is between 1 and 100
    const REG_NAME_GRADE_COLLECTION = "/^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]{1}[\w\sáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ'&@-_()]{0,99}[\wáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ)]{0,1}$/";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "name_collection", type: "string", length: 100, nullable: false, options: ["default" => "Unamed"])]
    private $nameCollection = "Unamed";

    #[ORM\Column(name: "grade_collection", type: "string", length: 100, nullable: false, options: ["default" => "Unamed"])]
    private $gradeCollection = "Unamed";

    public function __construct($id = 0, $nameCollection = "Unamed", $gradeCollection = "Unamed")
    {
        $this->id = $id;
        $this->nameCollection = $nameCollection;
        $this->gradeCollection = $gradeCollection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        if ($id > 0) {
            $this->id = $id;
        } else {
            throw new EntityDataIntegrityException("id needs to be integer and positive");
        }
    }

    public function getNameCollection(): string
    {
        return $this->nameCollection;
    }

    public function setNameCollection($nameCollection): void
    {
        if (preg_match(self::REG_NAME_GRADE_COLLECTION, $nameCollection)) {
            $this->nameCollection = $nameCollection;
        } else {
            throw new EntityDataIntegrityException("nameCollection needs to be string and have between 1 and 100 characters");
        }
    }

    public function getGradeCollection(): string
    {
        return $this->gradeCollection;
    }

    public function setGradeCollection($gradeCollection): void
    {
        if (preg_match(self::REG_NAME_GRADE_COLLECTION, $gradeCollection)) {
            $this->gradeCollection = $gradeCollection;
        } else {
            throw new EntityDataIntegrityException("gradeCollection needs to be string and have between 1 and 100 characters");
        }
    }

    public function copy($objectToCopyFrom): void
    {
        if ($objectToCopyFrom instanceof Collection) {
            $this->setNameCollection(urldecode($objectToCopyFrom->getNameCollection()));
            $this->setGradeCollection(urldecode($objectToCopyFrom->getGradeCollection()));
        } else {
            throw new EntityOperatorException("ObjectToCopyFrom attribute needs to be an instance of Collection");
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'nameCollection' => $this->nameCollection,
            'gradeCollection' => $this->gradeCollection,
        ];
    }

    public static function jsonDeserialize($json): Collection
    {
        $classInstance = new Collection();
        if (is_string($json)) {
            $json = json_decode($json);
        }
        foreach ($json as $key => $value) {
            $classInstance->{$key} = $value;
        }
        return $classInstance;
    }
}
