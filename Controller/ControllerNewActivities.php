<?php

namespace Learn\Controller;

use User\Entity\User;
use User\Entity\Regular;
use Learn\Entity\Activity;
use Learn\Controller\Controller;
use Classroom\Entity\Applications;
use Classroom\Entity\ActivityRestrictions;

class ControllerNewActivities extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(            
            'get_all_apps' => function () {
                $Apps = $this->entityManager->getRepository(Applications::class)->findAll();

                $Applications = [];
                foreach ($Apps as $app) {
                    $appli = $app->jsonSerialize();
                    $appsRestri = $this->entityManager->getRepository(ActivityRestrictions::class)->findOneBy(["application" => $appli["id"]]);
                    if ($appsRestri) {
                        $appli["type"] = $appsRestri->getActivityType();
                    }
                    $Applications[] = $appli;
                }

                return $Applications;
            },
            'create_exercice' => function ($data) {
                
                $title = !empty($data['title']) ? htmlspecialchars($data['title']) : null;
                $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                $content = !empty($data['content']) ? json_decode($data['content'], true) : null;
                $solution = !empty($data['solution']) ? htmlspecialchars($data['solution']) : null;
                $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;

                $regular = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $this->user['id']]);


                $exercice = new Activity($title, serialize($content), $regular, true);
                if ($solution) {
                    $exercice->setSolution($solution);
                }
                if ($tolerance) {
                    $exercice->setTolerance($tolerance);
                }
                if ($autocorrect) {
                    $exercice->setIsAutocorrect($autocorrect);
                } else {
                    $exercice->setIsAutocorrect(false);
                }

                $exercice->setType($type);

                $this->entityManager->persist($exercice);
                $this->entityManager->flush();
                
                return ['success' => true, 'id' => $exercice->getId()];
            },
            'get_one_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {
                        return $activity->jsonSerialize();
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
            'delete_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {
                        $this->entityManager->remove($activity);
                        $this->entityManager->flush();
                        return ['success' => true];
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
            'update_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {

                        $title = !empty($data['title']) ? htmlspecialchars($data['title']) : null;
                        $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                        $content = !empty($data['content']) ? json_decode($data['content'], true) : null;
                        $solution = !empty($data['solution']) ? htmlspecialchars($data['solution']) : null;
                        $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                        $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;

                        $activity->setTitle($title);
                        $activity->setType($type);
                        $activity->setContent(serialize($content));
                        if ($solution) {
                            $activity->setSolution($solution);
                        }
                        if ($tolerance) {
                            $activity->setTolerance($tolerance);
                        }
                        if ($autocorrect) {
                            $activity->setIsAutocorrect($autocorrect);
                        } else {
                            $activity->setIsAutocorrect(false);
                        }

                        $this->entityManager->persist($activity);
                        $this->entityManager->flush();
                        return ['success' => true];
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
            "save_new_activity" => function () {


                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];


                $isRegular = $this->entityManager->getRepository(Regular::class)->findOneBy(['id' => $_SESSION['id']]);

                // Basics data 
                $activityId = !empty($_POST['id']) ? intval($_POST['id']) : 0;
                $timePassed = !empty($_POST['timePassed']) ? intval($_POST['timePassed']) : 0;
                $autocorrect = !empty($_POST['autocorrect']) ? htmlspecialchars($_POST['autocorrect']) : null;
                // Student's part 
                $response = !empty($_POST['response']) ? json_decode($_POST['response'], true) : null;
                // Teacher's part
                // Correction 0 = correction, 1 = no correction
                $correction = !empty($_POST['correction']) ? intval($_POST['correction']) : null;
                $commentary = !empty($_POST['commentary']) ? htmlspecialchars(strip_tags(trim($_POST['commentary']))) : '';
                $note = !empty($_POST['note']) ? intval($_POST['note']) : 0;


                // initiate an empty errors array 
                $errors = [];
                if (empty($activityId)) $errors['invalidActivityId'] = true;
                if (empty($correction)) $errors['invalidCorrection'] = true;

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, get the activity
                $activity = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')->findOneBy(array("id" => $activityId));

                if ($isRegular) {
                    $activity->setCorrection($correction);
                    $activity->setNote($note);
                    $activity->setCommentary($commentary);
                }
                $activity->setResponse(serialize($response));

                // Basic autocorrect management
                if ($autocorrect) {
                    $solution = $activity->getSolution();
                    if ($solution == $response) {
                        $activity->setNote(3);
                    } else {
                        $activity->setNote(0);
                    }
                }
            
                $this->entityManager->persist($activity);
                $this->entityManager->flush();

                return  $activity;
            },
        );
    }
}
