<?php

namespace Learn\Controller;

use User\Entity\User;
use User\Entity\Regular;
use Learn\Entity\Activity;
use Learn\Controller\Controller;
use Classroom\Entity\Applications;
use Classroom\Entity\ActivityRestrictions;
use Classroom\Entity\ActivityLinkUser;

class ControllerNewActivities extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(            
            'get_all_apps' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

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

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

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
                    if ($autocorrect == "true") {
                        $exercice->setIsAutocorrect(true);
                    } else {
                        $exercice->setIsAutocorrect(false);
                    }
                } else {
                    $exercice->setIsAutocorrect(false);
                }

                $exercice->setType($type);

                $this->entityManager->persist($exercice);
                $this->entityManager->flush();
                
                return ['success' => true, 'id' => $exercice->getId()];
            },
            'get_one_activity' => function ($data) {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

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

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

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

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

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
                        return ['success' => true, 'id' => $activity->getId()];
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


                $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                $isRegular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);

                // Basics data 
                $activityId = !empty($_POST['id']) ? intval($_POST['id']) : 0;
                $timePassed = !empty($_POST['timePassed']) ? intval($_POST['timePassed']) : 0;
                // Student's part 
                $response = !empty($_POST['response']) ? $_POST['response'] : null;
                // Teacher's part
                $commentary = !empty($_POST['commentary']) ? htmlspecialchars(strip_tags(trim($_POST['commentary']))) : '';
                $note = !empty($_POST['note']) ? intval($_POST['note']) : 0;


                // initiate an empty errors array 
                $errors = [];
                if (empty($activityId)) $errors['invalidActivityId'] = true;

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, get the activity
                $acti = $this->entityManager->getRepository(Activity::class)->find($activityId);
                $activity = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')->findOneBy(["activity" => $activityId, "user" => $_SESSION['id']]);


                // Correction 0 = no correction, 1 =  waiting correction, 2 correction

                if ($acti) {

                    // If it's the teacher who send the request
                    if ($isRegular) {
                        $activity->setCorrection(2);
                        $activity->setNote($note);
                        $activity->setCommentary($commentary);
                    } else {
                        $activity->setCorrection(1);
                    }
                    $activity->setResponse($response);
    
                    if ($timePassed) {
                        $activity->setTimePassed($timePassed);
                    }

                    // Manage auto correction for every activity type
                    if ($acti->getIsAutocorrect() == true) {
                        var_dump($acti->getType());
                        if ($acti->getType() == "fillIn") {
                            $activity = $this->manageFillInAutocorrection($acti, $activity, $response);
                        } else if ($acti->getType() == "free" || $acti->getType() == "") {
                            $activity = $this->manageFreeAutocorrection($acti, $activity, $response);      
                        }
                        // Set the correction to 2 (activity corrected)
                        $activity->setCorrection(2);
                    }
                
                    $this->entityManager->persist($activity);
                    $this->entityManager->flush();
    
                    return  $activity;
                } else {
                    return ["error" => "Activity not found"];
                }  
            }
        );
    }

    private function manageFreeAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response) {
        $solution = $activity->getSolution();
        if (strtolower($solution) == strtolower($response)) {
            $activityLinkUser->setNote(3);
        } else {
            $activityLinkUser->setNote(0);
        }
        return $activityLinkUser;
    }

    private function manageFillInAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response) {
    

        $solution = json_decode($activity->getSolution(), true);

        var_dump($solution);
        $tolerance = $activity->getTolerance();
        $isCorrect = true;
        foreach ($solution as $key => $value) {
            if ($response[$key] != $value) {
                $isCorrect = false;
            }
        }
        if ($isCorrect) {
            $activityLinkUser->setNote(3);
        } else {
            $activityLinkUser->setNote(0);
        }
        die();
        return $activityLinkUser;


    }


}
