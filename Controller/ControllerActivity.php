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
            'add' => function () {

                // bind and sanitize incoming data
                $title = isset($_POST['title'])
                            ? trim(htmlspecialchars(preg_replace('/<[^>]*>[^<]*<[^>]*>/', '',$_POST['title'])))
                            :'';
                $content = isset($_POST['content']) 
                            ? trim(htmlspecialchars(preg_replace('/<[^>]*>[^<]*<[^>]*>/', '',$_POST['content'])))
                            :'';
                $isFromClassroom = isset($_POST['isFromClassroom']) && is_bool($_POST['isFromClassroom'])
                                    ? $_POST['isFromClassroom']
                                    : null;
                $userId = isset($this->user['id'])
                            ? intval($this->user['id'])
                            : null;
                $forkedActivityId = isset($_POST['id']) ? intval($_POST['id']) : null;
                if(isset($_POST['isFromClassroom']) && filter_var($_POST['isFromClassroom'], FILTER_VALIDATE_BOOLEAN)){
                    $isFromClassroom = $_POST['isFromClassroom']
                                    ? (bool) $_POST['isFromClassroom']
                                    : null;
                }

                // create empty errors array and check for errors
                $errors = [];
                if(empty($title)) $errors['activityTitleInvalid'] = true; 
                if(empty($content)) $errors['activityContentInvalid'] = true;
                if(empty($isFromClassroom)) $errors['activityIsFromClassroomInvalid'] = true;
                if($userId == null) $errors['activityUserIdInvalid'] = true;

                // return errors if any
                if(!empty($errors)){
                    return array(
                        'errors' => $errors
                    );
                }
                
                // no errors found, we can process the data
                // get the user 
                $user = $this->entityManager
                                ->getRepository('User\Entity\User')
                                ->find($userId);

                if(!$user){
                    // return an error if the user was not found
                    return array(
                        'error'=> 'userNotExists'
                    );
                }
                

                // create the activity
                $activity = new Activity( $title , $content, $user, $isFromClassroom);

                // if the activity belongs to vittasciences resources, set the resources id 
                if ($forkedActivityId != null) {
                    $fork = $this->entityManager->getRepository('Learn\Entity\Activity')
                        ->find($forkedActivityId);
                    $activity->setFork($fork);
                }

                // persist and save the activity, then return the activity
                $this->entityManager->persist($activity);
                $this->entityManager->flush();
                return $activity;

            }, 
            'add_several' => function ($data) {
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
