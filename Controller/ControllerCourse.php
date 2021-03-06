<?php

namespace Learn\Controller;

use Learn\Entity\Course;
use Learn\Entity\Activity;
use Learn\Entity\CourseLinkActivity;
use Learn\Entity\Lesson;
use User\Entity\User;
use Learn\Entity\CourseLinkCourse;
use Database\DataBaseManager;

/* require_once(__DIR__ . '/../../../utils/resize_img.php'); */

class ControllerCourse extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_all' => function () {
                return $this->entityManager->getRepository('Learn\Entity\Course')
                    ->findAll();
            },

            'get_one' => function ($data) {
                preg_match('/[0-9]{1,11}/', $data['id'], $matches);
                $data['id'] = $matches[0];
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')
                    ->find($data['id']);
                $activities = $this->entityManager->getRepository('Learn\Entity\CourseLinkActivity')
                    ->getActivitiesOrdered($data['id']);
                $arrayActivities = [];
                for ($index = 0; $index < count($activities); $index++) {
                    $arrayActivities[$index] = $activities[$index]->getActivity();
                }
                $tutorial = array(
                    "tutorial" => $tutorial,
                    "activities" => $arrayActivities
                );
                return $tutorial;
            },
            'get_by_user' => function () {
                if (isset($_GET['limit'])) {
                    $limit  = $_GET['limit'];
                    return $this->entityManager->getRepository('Learn\Entity\Course')
                        ->findBy(
                            array("user" => $this->user),
                            null, //order
                            $limit
                        );
                } else {
                    return $this->entityManager->getRepository('Learn\Entity\Course')
                        ->findBy(
                            array("user" => $this->user)
                        );
                }
            },
            'count_my_tutorials' => function () {
                return count($this->entityManager->getRepository('Learn\Entity\Course')
                    ->findBy(
                        array("user" => $this->user)
                    ));
            },
            'get_by_filter' => function ($data) {
                $search = "'%" . $data['filter']['search'] . "%'";
                if ($this->user != null) {
                    $id = $this->user['id'];
                } else {
                    $id = 0;
                }
                unset($data['filter']['search']);
                $result = $this->entityManager->getRepository('Learn\Entity\Course')->getByFilter($data['filter'], $id, $search, $data['page']);
                $arrayResult = [];
                foreach ($result as $r) {
                    if (json_encode($r) != NULL && json_encode($r) != false) {
                        $arrayResult[] = $r;
                    }
                }
                return $arrayResult;
            },
            'count_by_filter' => function ($data) {
                if ($this->user != null) {
                    $id = $this->user['id'];
                } else {
                    $id = 0;
                }
                $search = "'%" . $data['filter']['search'] . "%'";
                unset($data['filter']['search']);
                return $this->entityManager->getRepository('Learn\Entity\Course')->countByFilter($data['filter'], $id, $search);
            },
            'add' => function () {
                // accept only POST request
                if($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error"=> "Method not Allowed"];

                // accept only connected user
                if(empty($_SESSION['id'])) return ["error"=> "Not Authenticated"];

                try {

                    $tutorialParts = json_decode($_POST['tutorialParts']);
                    $chapters = json_decode($_POST['chapters']);
                    $linked = json_decode($_POST['linkedTuto']);
                    unset($_POST['linkedTuto']);
                    unset($_POST['tutorialParts']);
                    unset($_POST['chapters']);

                    // translate first $tutorialPart content from $name and url to full bbcode
                    for($i=0 ; $i < count($tutorialParts); $i++){

                        if($i === 0 ){
                            // initialize $content to be concatenated with sanitized values later
                            $content = "[fa-list]";

                            // first content property is an array of names and urls
                            $nameAndUrlPairs = $tutorialParts[$i]->content;

                            foreach($nameAndUrlPairs as $nameAndUrlPair){
                                // bind and sanitize incoming data
                                $name = htmlspecialchars(strip_tags(trim($nameAndUrlPair->name)));
                                $url = htmlspecialchars(strip_tags(trim($nameAndUrlPair->url)));

                                // fill content with sanitized values
                                $content .= empty($name) 
                                            ? "[fa-*]{$name}[\/*]" 
                                            : "[*][fa-url={$url}]{$name}[/url][/*]";
                            }
                            $content .= "[/list]";
                            $tutorialParts[$i]->content = $content;
                        }
                        else {
                            // bind and sanitize incoming data
                            $title = htmlspecialchars(strip_tags(trim($tutorialParts[$i]->title)));
                            $content = htmlspecialchars(strip_tags(trim($tutorialParts[$i]->content)));

                            // replace values by the same values but sanitized
                            $tutorialParts[$i]->title =  $title;
                            $tutorialParts[$i]->content =  $content;
                        }
                    }
                
                    $tutorial = Course::jsonDeserialize($_POST);
                    if (isset($_FILES['imgFile'])) {
                        $tutorial->setImg($_FILES['imgFile']);
                    }
                    $user = $this->entityManager->getRepository('User\Entity\User')->find($this->user['id']);
                    $tutorial->setUser($user);
                    $tutorial->setCreatedAt();
                    //add lessons to the tutorial
                    foreach ($chapters as $chapter) {
                        $chapter = $this->entityManager->getRepository('Learn\Entity\Chapter')->find($chapter);
                        $lesson = new Lesson();
                        $lesson->setCourse($tutorial);
                        $lesson->setChapter($chapter);
                        $this->entityManager->persist($lesson);
                    }

                    foreach ($linked as $tuto) {
                        $tutorial2 = $this->entityManager->getRepository('Learn\Entity\Course')->find($tuto);
                        $related = new CourseLinkCourse($tutorial, $tutorial2);
                        $this->entityManager->persist($related);
                    }

                    $this->entityManager->persist($tutorial);
                    //add parts to the tutorial
                    for ($index = 0; $index < count($tutorialParts); $index++) {
                        $activity = new Activity($tutorialParts[$index]->title, $tutorialParts[$index]->content);
                        $this->entityManager->persist($activity);
                        $courseLinkActivity = new CourseLinkActivity($tutorial, $activity, $index);
                        $this->entityManager->persist($courseLinkActivity);
                    }
                    $this->entityManager->flush();
                    return $tutorial;
                } catch (\Error $error) {
                    echo ($error->getMessage());
                }
            },

            'increment_views' => function ($data) {
                if (isset($_SESSION['views'][$data['id']])) {
                    return false;
                } else {
                    $_SESSION['views'][$data['id']] = 1;
                    $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->findOneBy(array("id" => $data['id']));
                    $tutorial->incrementViews();
                    $this->entityManager->persist($tutorial);
                    $this->entityManager->flush();

                    return true;
                }
            },
            'update' => function () {

                // accept only POST request
                if($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error"=> "Method not Allowed"];

                // accept only connected user
                if(empty($_SESSION['id'])) return ["errorType"=> "courseNotUpdatedNotAuthenticated"];

                // bind and sanitize data
                $userId = intval($_SESSION['id']);
                $courseId = intval($_POST['id']);

                // check if user is admin
                $isAdmin = $this->entityManager->getRepository(Regular::class)->findOneBy(array(
                    'user'=> $userId,
                    'isAdmin' => true
                ));

                //check if the userr is the owner of the course
                $isOwnerOfCourse = $this->entityManager->getRepository(Course::class)->findOneBy(array(
                    'id' => $courseId,
                    'user'=> $userId
                ));

               // user is not admin and not the owner of the course
                if(!$isAdmin && !$isOwnerOfCourse ){
                    return ["errorType"=> "courseNotUpdatedNotAuthorized"];
                }

                //prepare the data
                $tutorialParts = json_decode($_POST['tutorialParts']);
                $linked = json_decode($_POST['linkedTuto']);
                $chapters = json_decode($_POST['chapters']);
                unset($_POST['tutorialParts']);
                unset($_POST['chapters']);
                unset($_POST['linkedTuto']);
               
                // translate first $tutorialPart content from $name and url to full bbcode
                for($i=0 ; $i < count($tutorialParts); $i++){

                    if($i === 0 ){
                        // initialize values
                        $content = "[fa-list]";
                        $nameAndUrlPairs = $tutorialParts[$i]->content;

                        foreach($nameAndUrlPairs as $nameAndUrlPair){
                            // bind and sanitize incoming data
                            $name = htmlspecialchars(strip_tags(trim($nameAndUrlPair->name)));
                            $url = htmlspecialchars(strip_tags(trim($nameAndUrlPair->url)));

                            // fill content with sanitized values
                            $content .= empty($name) 
                                        ? "[fa-*]{$name}[\/*]" 
                                        : "[*][fa-url={$url}]{$name}[/url][/*]";
                        }
                        $content .= "[/list]";
                        $tutorialParts[$i]->content = $content;
                    }
                    else {
                         // bind and sanitize incoming data
                        $title = htmlspecialchars(strip_tags(trim($tutorialParts[$i]->title)));
                        $content = htmlspecialchars(strip_tags(trim($tutorialParts[$i]->content)));

                        // replace values by the same values but sanitized
                        $tutorialParts[$i]->title =  $title;
                        $tutorialParts[$i]->content =  $content;
                    }
                }
                
                $tutorial = Course::jsonDeserialize($_POST);
                //get the matching tutorial from database
                $databaseCourse = $this->entityManager->getRepository('Learn\Entity\Course')->findOneBy(array("id" => $tutorial->getId()));

                //if we uploaded a picture, set it on the database & tutorial
                if (isset($_FILES['imgFile'])) {
                    $tutorial->setImg($_FILES['imgFile']);
                }
                //delete previous lessons & parts & linked from the database
                $lessonsDatabase = $this->entityManager->getRepository('Learn\Entity\Lesson')->findBy(array("tutorial" => $tutorial));
                foreach ($lessonsDatabase as $val) {
                    $this->entityManager->remove($val);
                }
                $activities = $this->entityManager->getRepository('Learn\Entity\CourseLinkActivity')
                    ->getActivitiesOrdered($tutorial->getId());
                $arrayActivities = [];
                for ($index = 0; $index < count($activities); $index++) {
                    $this->entityManager->remove($activities[$index]);
                    $arrayActivities[$index] = $activities[$index]->getActivity();
                }
                foreach ($arrayActivities as $val) {
                    $this->entityManager->remove($val);
                }
                $linkedDatabase = $this->entityManager->getRepository('Learn\Entity\CourseLinkCourse')->findBy(array("tutorial1" => $tutorial));
                foreach ($linkedDatabase as $val) {
                    $this->entityManager->remove($val);
                }
                $this->entityManager->flush();

                $databaseCourse->copy($tutorial);

                $this->entityManager->persist($databaseCourse);
                $this->entityManager->flush();

                //add parts to the tutorial
                for ($index = 0; $index < count($tutorialParts); $index++) {
                    $activity = new Activity($tutorialParts[$index]->title, $tutorialParts[$index]->content, $this->entityManager->getRepository('User\Entity\User')
                        ->find($this->user['id']));
                       
                    $this->entityManager->persist($activity);
                    $this->entityManager->flush();
                    $courseLinkActivity = new CourseLinkActivity($databaseCourse, $activity, $index);
                    try {
                        $this->entityManager->persist($courseLinkActivity);
                    } catch (\Error $e) {
                    }

                    $this->entityManager->flush();
                }

                //add lessons to the tutorial
                foreach ($chapters as $chapter) {
                    $chapter = $this->entityManager->getRepository('Learn\Entity\Chapter')->find($chapter);
                    $lesson = new Lesson();
                    $lesson->setCourse($databaseCourse);
                    $lesson->setChapter($chapter);
                    $this->entityManager->persist($lesson);
                }

                //add linked tutorials
                foreach ($linked as $tuto) {
                    $tutorial2 = $this->entityManager->getRepository('Learn\Entity\Course')->find($tuto);
                    $related = new CourseLinkCourse($databaseCourse, $tutorial2);
                    $this->entityManager->persist($related);
                }

                $this->entityManager->flush();
                return $databaseCourse;
            },
            'delete' => function ($data) {
                $databaseCourse = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['id']);
                $lessonsDatabase = $this->entityManager->getRepository('Learn\Entity\Lesson')->findBy(array("tutorial" => $databaseCourse));
                foreach ($lessonsDatabase as $val) {
                    $this->entityManager->remove($val);
                }

                $favoriteDatabase = $this->entityManager->getRepository('Learn\Entity\Favorite')->findBy(array("tutorial" => $databaseCourse));
                foreach ($favoriteDatabase as $val) {
                    $this->entityManager->remove($val);
                }
                $this->entityManager->flush();
                $this->entityManager->remove($databaseCourse);
                $this->entityManager->flush();
            },
            'get_all_user_resources' => function ($data) {
                // To change
                if ($data['user']) {
                    $idUserToFetch = $data['user'];
                } else {
                    $idUserToFetch = $this->user['id'];
                }
                $userFetched = $this->entityManager->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $idUserToFetch));
                if ($userFetched === null) {
                    return [];
                } else {
                    if ($data['user']) {
                        return $this->entityManager->getRepository('Learn\Entity\Course')
                            ->findBy(array("user" => $userFetched, "deleted" => false, "rights" => [1, 2]));
                    } else {
                        return $this->entityManager->getRepository('Learn\Entity\Course')
                            ->findBy(array("user" => $userFetched, "deleted" => false));
                    }
                }
            }
        );
    }
}
