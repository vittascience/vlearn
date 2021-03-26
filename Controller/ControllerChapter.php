<?php

namespace Learn\Controller;

use Learn\Entity\Collection;
use Learn\Entity\Chapter;

class ControllerChapter extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_all' => function () {
                return $this->entityManager->getRepository('Learn\Entity\Chapter')
                    ->findAll();
            },
            'get_one' => function ($data) {
                return $this->entityManager->getRepository("Learn\Entity\Chapter")
                    ->findBy(array("id" => $data['id']));
            },
            'get_tutorial_chapters' => function ($data) {
                $chapters = $this->entityManager->getRepository("Learn\Entity\Chapter")
                    ->findBy(array("tutorial_id" => $data['tutoRef']));
                $arrayResult = array();
                foreach ($chapters as $chapter) {
                    $result = ["id" => $chapter->getId(), "name" => $chapter->getName(), "collection" => $chapter->getCollection()->getName()];
                    array_push($arrayResult, $result);
                }
                return $arrayResult;
            },
            'get_chapter_by_collection' => function ($data) {
                $collection = new Collection($data['id'], $data['nameCollection'], $data['gradeCollection']);
                $chapters = $this->entityManager->getRepository("Learn\Entity\Chapter")
                    ->findBy(array("collection" => $collection));
                $arrayResult = array();
                foreach ($chapters as $chapter) {
                    $result = ["id" => $chapter->getId(), "name" => $chapter->getName()];
                    array_push($arrayResult, $result);
                }
                return $arrayResult;
            }
        );
    }
}
