<?php

namespace Learn\Controller;

use Learn\Entity\Activity;
use Database\DataBaseManager;

/* require_once(__DIR__ . '/../../../utils/resize_img.php'); */

class ControllerActivity extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(

            'get_mine' => function () {
                return $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->findBy(
                        array("user" => $this->user)
                    );
            },
            'get_mine_for_classroom' => function () {
                return $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->findBy(
                        array("user" => $this->user, "isFromClassroom" => true)
                    );
            },
            'get' => function ($data) {
                return $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->find($data['id']);
            },
            'delete' => function ($data) {
                $activity = $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->find($data['id']);
                $name = ["name" => $activity->getTitle(), "id" => $activity->getId()];
                $this->entityManager->remove($activity);
                $this->entityManager->flush();
                return $name;
            },
            'add' => function ($data) {
                $isFromClassroom = false;
                if (isset($data['isFromClassroom'])) {
                    $isFromClassroom = true;
                }
                $user = $this->entityManager->getRepository('User\Entity\User')
                    ->find($this->user['id']);
                $activity = new Activity($data['title'], $data['content'], $user, $isFromClassroom);
                if (isset($data['id'])) {
                    $fork = $this->entityManager->getRepository('Learn\Entity\Activity')
                        ->find($data['id']);
                    $activity->setFork($fork);
                }
                $this->entityManager->persist($activity);
                $this->entityManager->flush();
                return $activity;
            }, 'add_several' => function ($data) {
                $activities = [];
                foreach ($data["array"] as $d) {
                    $isFromClassroom = false;
                    if (isset($data['isFromClassroom'])) {
                        $isFromClassroom = true;
                    }
                    $user = $this->entityManager->getRepository('User\Entity\User')
                        ->find($this->user['id']);
                    $activity = new Activity($d['title'], $d['content'], $user, $isFromClassroom);
                    if (isset($d['id'])) {
                        $fork = $this->entityManager->getRepository('Learn\Entity\Activity')
                            ->find($d['id']);
                        $activity->setFork($fork);
                    }
                    $this->entityManager->persist($activity);
                    $activities[] = $activity;
                }
                $this->entityManager->flush();
                return $activities;
            },
            'update' => function ($data) {
                $activity = $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->find($data['id']);
                $activity->setTitle($data['title']);
                $activity->setContent($data['content']);
                $this->entityManager->persist($activity);
                $this->entityManager->flush();

                return $activity;
            },
        );
    }
}
