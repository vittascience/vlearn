<?php

namespace Learn\Controller;

class ControllerLesson extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_by_tutorial' => function ($data) {
                $lessons = $this->entityManager->getRepository('Learn\Entity\Lesson')
                    ->findBy(array("tutorial" => $data['id']));
                $arrayResult = array();
                foreach ($lessons as $lesson) {
                    $result = [
                        "chapter" => $lesson->getChapter()->getId(),
                        "collection" => $lesson->getChapter()->getCollection()->getId(),
                        "collectionName" => $lesson->getChapter()->getCollection()->getNameCollection(),
                        "chapterName" => $lesson->getChapter()->getName(),
                    ];
                    array_push($arrayResult, $result);
                }
                return  $arrayResult;
            }
        );
    }
}
