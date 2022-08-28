<?php

namespace Learn\Controller;

use User\Entity\User;
use User\Entity\Regular;
use Learn\Entity\Folders;
use Learn\Entity\Activity;
use Classroom\Entity\Groups;
use Learn\Controller\Controller;
use Classroom\Entity\Applications;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\UsersRestrictions;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\UsersLinkApplicationsFromGroups;
use Classroom\Entity\GroupsLinkApplications;

class ControllerNewActivities extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(            
            'get_all_apps' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $ShowerApps = [];
                $Apps = $this->entityManager->getRepository(Applications::class)->findAll();

                // datetime now
                $now = new \DateTime();

                // for each apps check if it is active and allow to use it
                foreach ($Apps as $app) {

                    $users_link_applications = $this->entityManager->getRepository(UsersLinkApplications::class)->findOneBy(['application' => $app->getId(), 'user' => $this->user]);
                    $users_link_applications_from_groups = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findOneBy(['application' => $app->getId(), 'user' => $this->user]);
                    $appSerialized = $app->jsonSerialize();


                    if ($app->getMaxPerTeachers() == -1 || $app->getMaxPerTeachers() > 0) {
                        $ShowerApps[] = $appSerialized;
                    } else if ($users_link_applications || $users_link_applications_from_groups) {

                        if ($users_link_applications) {
                            // UsersRestrictions
                            $userRestriction = $this->entityManager->getRepository(UsersRestrictions::class)->findOneBy(['user' => $this->user]);
                            $dateBegin = $userRestriction->getDateBegin() ?? null;
                            $dateEnd = $userRestriction->getDateEnd() ?? null;
                            $appSerialized['dateEnd'] = $dateEnd;
                            
                            if ($users_link_applications->getmaxActivitiesPerTeachers() == -1 ||
                                $users_link_applications->getmaxActivitiesPerTeachers() > 0) {
                                if ($dateBegin && $dateEnd >= $now) {
                                    $appSerialized['outDated'] = false;
                                    $ShowerApps[] = $appSerialized;
                                } else {
                                    $appSerialized['outDated'] = true;
                                    $ShowerApps[] = $appSerialized;
                                }
                            }
                            
                            
                        } else if ($users_link_applications_from_groups) {

                            $groups = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $users_link_applications_from_groups->getGroup()->getId()]);
                            $dateBegin = $groups->getDateBegin() ?? null;
                            $dateEnd = $groups->getDateEnd() ?? null;
                            $appSerialized['dateEnd'] = $dateEnd;
                            
                            $applicationRestrictionsFromGroup = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $users_link_applications_from_groups->getGroup(), 'application' => $users_link_applications_from_groups->getApplication()]);

                            if ($applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers() == -1 ||
                                $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers() > 0) {

                                if ($dateBegin && $dateEnd >= $now) {
                                    $appSerialized['outDated'] = false;
                                    $ShowerApps[] = $appSerialized;
                                } else {
                                    $appSerialized['outDated'] = true;
                                    $ShowerApps[] = $appSerialized;
                                }
                            }
                        }

                    }
                }

                return $ShowerApps;
            },
            'create_exercice' => function ($data) {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $title = !empty($data['title']) ? htmlspecialchars($data['title']) : null;
                $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                $content = !empty($data['content']) ? json_decode($data['content'], true) : null;
                $solution = !empty($data['solution']) ? json_decode($data['solution'], true) : null;
                $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;
                $folderId = !empty($data['folder']) ? htmlspecialchars($data['folder']) : null;

                $regular = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $this->user['id']]);

                $exercice = new Activity($title, serialize($content), $regular, true);

                if ($solution) {
                    $exercice->setSolution(serialize($solution));
                }

                if ($tolerance) {
                    $exercice->setTolerance($tolerance);
                }

                if ($folderId != null) {
                    $folder = $this->entityManager->getRepository(Folders::class)->find($folderId);
                    if ($folder) {
                        $exercice->setFolder($folder);
                    }
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
                        // step 1 database cleaning
                        // get all students activity matching with the activity 
                        $studentsActivity = $this->entityManager
                            ->getRepository(ActivityLinkUser::class)
                            ->findBy(array(
                                'activity' => $activity
                            ));
                        
                        // some record found, delete them from classroom_activities_link_classroom_users
                        if($studentsActivity){
                            foreach($studentsActivity as $studentActivity){
                                $this->entityManager->remove($studentActivity);
                                $this->entityManager->flush();
                            }
                        }
                       
                        // step 2 database cleaned, we can delete the activity
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

                        $title = !empty($data['title']) ? $data['title'] : null;
                        $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                        $content = !empty($data['content']) ? json_decode($data['content'], true) : null;
                        $solution = !empty($data['solution']) ? json_decode($data['solution'], true) : null;
                        $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                        $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;

                        $activity->setTitle($title);
                        $activity->setType($type);
                        $activity->setContent(serialize($content));

                        if ($solution) {
                            $activity->setSolution(serialize($solution));
                        }
                        if ($tolerance) {
                            $activity->setTolerance($tolerance);
                        }
                        if ($autocorrect != null) {
                            if ($autocorrect == "true") {
                                $activity->setIsAutocorrect(true);
                            } else {
                                $activity->setIsAutocorrect(false);
                            }
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
                $activityLinkId = !empty($_POST['activityLinkUserId']) ? intval($_POST['activityLinkUserId']) : 0;
                $timePassed = !empty($_POST['timePassed']) ? intval($_POST['timePassed']) : 0;
                $correction = !empty($_POST['correction']) ? intval($_POST['correction']) : 0;

                // Student's part 
                $response = !empty($_POST['response']) ? $_POST['response'] : null;

                // Teacher's part
                $commentary = !empty($_POST['commentary']) ? htmlspecialchars(strip_tags(trim($_POST['commentary']))) : '';
                $note = !empty($_POST['note']) ? intval($_POST['note']) : 0;


                if($this->isJson($response)) {
                    $response = json_decode($response, true);
                }

                // initiate an empty errors array 
                $errors = [];
                if (empty($activityId)) $errors['invalidActivityId'] = true;

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, get the activity
                $acti = $this->entityManager->getRepository(Activity::class)->find($activityId);
                $activity = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(["id" => $activityLinkId]);

                
                $actualTries = $activity->getTries();

                if ($correction > 0) {
                    $activity->setTries($actualTries + 1);
                }

                $content = "";
                $unserialized = @unserialize($acti->getContent());
                if ($unserialized) {
                    $content = $unserialized;
                } else {
                    $content = $acti->getContent();
                }

                $hint = "";
                if (is_array($content)) {
                    if (array_key_exists('hint', $content)) {
                        $hint = $content['hint'];
                    }
                }
                   
                if ($actualTries > 1 && $activity->getEvaluation() == 1) {
                    return false;
                }

                if ($acti) {

                    // If it's the teacher who send the request
                    if ($isRegular) {
                        $activity->setNote($note);
                        $activity->setCommentary($commentary);
                    }

                    $activity->setResponse(serialize($response));
    
                    if ($timePassed) {
                        $activity->setTimePassed($timePassed);
                    }

                    // Manage auto correction for every activity type
                    $errorsArray = [];

                    if ($acti->getType() == "fillIn") {
                        $fillInReturn = $this->manageFillInAutocorrection($acti, $activity, $response, $acti->getIsAutocorrect());
                        $activity = $fillInReturn[0];
                        $errorsArray = $fillInReturn[1];
                    } else if (($acti->getType() == "free" || $acti->getType() == "")) {
                        $freeReturn = $this->manageFreeAutocorrection($acti, $activity, $response, $acti->getIsAutocorrect());     
                        $activity = $freeReturn[0];
                        $errorsArray = $freeReturn[1];
                    } else if ($acti->getType() == "quiz") {
                        $quizReturn = $this->manageQuizAutocorrection($acti, $activity, $response, $acti->getIsAutocorrect());
                        $activity = $quizReturn[0];
                        $errorsArray = $quizReturn[1];
                    } else if ($acti->getType() == "dragAndDrop") {
                        $dragAndDropReturn = $this->manageDragAndDropAutocorrection($acti, $activity, $response, $acti->getIsAutocorrect());
                        $activity = $dragAndDropReturn[0];
                        $errorsArray = $dragAndDropReturn[1];
                    }
  
                    // Set the correction to 2 (activity corrected)
                    if ($acti->getIsAutocorrect() && $activity->getEvaluation() == 1 && $correction > 0) {
                        $activity->setCorrection(2);
                    } else if ($acti->getIsAutocorrect() && $activity->getEvaluation() != 1 && $correction > 0) {
                        if (count($errorsArray) > 0) {
                            $activity->setCorrection(1);
                        } else {
                            $activity->setCorrection(2);
                        }
                    } else {
                        $activity->setCorrection($correction);
                    }
                
                    $this->entityManager->persist($activity);
                    $this->entityManager->flush();

                    if ($correction == 0) {
                        return ['success' => true, 'message' => "activitySaved"];
                    }

                    if (count($errorsArray) > 0 && $activity->getEvaluation() != 1) {
                        if (empty($response)) {
                            return ['success'=> false, 'message' => 'emptyAnswer'];
                        }

                        return ['badResponse' => $errorsArray, 'hint' => $hint];
                    }

                    return  $activity;

                } else {
                    return ["error" => "Activity not found"];
                } 
            },
            "duplicate_activity" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                $isRegular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);

                // Basics data 
                $activityId = !empty($_POST['activityId']) ? intval($_POST['activityId']) : 0;
               
                if ($activityId && $isRegular) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($activityId);

                    $title = "";
                    $newTitle = "";
                    $regexTrigger = "";

                    if ($activity->getTitle()) {
                        $title = $activity->getTitle();
                        $regexTrigger = preg_match('/\(.*?\)/', $activity->getTitle(), $title);
                        if ($regexTrigger) {
                            $result = str_replace('(', '', $title);
                            $number = str_replace(')', '', $result);
                            $increment = (intval($number[0]) + 1);
                            $newTitle = str_replace($title, "(" . $increment . ")", $activity->getTitle());
                        } else {
                            $title = 1;
                            $newTitle = $activity->getTitle() . " (1)";
                        }
                    }

                    $isLti = false;
                    if ($activity->getType()) {
                        // get application from the restriction
                        $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['name' => $activity->getType()]);
                        // check if the application is lti
                        if ($application->getIsLti() == true) {
                            $isLti = true;
                        }
                    }

                    // Add duplicate parameter if we are in lti activity case
                    $unserialized = @unserialize($activity->getContent());
                    if ($unserialized) {
                        $content = json_encode($unserialized);
                    } else {
                        $content = $activity->getContent();
                    }
                    if ($isLti) {
                        $content = json_decode($content, true);
                        if (!str_contains($content["description"], "&duplicate=1")) {
                            $content["description"] .= "&duplicate=1";
                        }
                        $content = json_encode($content);
                    }

                    $duplicatedActivity = new Activity( $newTitle,  
                                                        $content, 
                                                        $activity->getUser(), 
                                                        $activity->isFromClassroom());


                    if ($activity->getType()) {
                        $duplicatedActivity->setType($activity->getType());
                    }
                    if ($activity->getSolution()) {
                        $duplicatedActivity->setSolution($activity->getSolution());
                    }
                    if ($activity->getTolerance()) {
                        $duplicatedActivity->setTolerance($activity->getTolerance());
                    }
                    if ($activity->getIsAutocorrect()) {
                        $duplicatedActivity->setIsAutocorrect($activity->getIsAutocorrect());
                    }
                    if ($activity->getFork() != null) {
                        $duplicatedActivity->setFork($activity->getFork()->jsonSerialize());
                    } else {
                        $duplicatedActivity->setFork(null);
                    }

                    if ($activity->getFolder() != null) {
                        $duplicatedActivity->setFolder($activity->getFolder());
                    }

                    $this->entityManager->persist($duplicatedActivity);
                    $this->entityManager->flush();
                    
                    return  ['success' => true, 'id' => $duplicatedActivity->getId()];
                } else {
                    return ["error" => "Activity not found"];
                } 
            },
            "get_autocorrect_result" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];


                // get id activity and id activity link user
                $activityId = !empty($_POST['activityId']) ? intval($_POST['activityId']) : 0;
                $activityLinkId = !empty($_POST['activityLinkId']) ? intval($_POST['activityLinkId']) : 0;

                if ($activityId && $activityLinkId) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($activityId);
                    $activityLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(["id" => $activityLinkId]);

                    if ($activity && $activityLinkUser) {
                        $errorsArray = [];
                        if ($activity->getType() == "fillIn") {
                            $errorsArray = $this->manageFillInAutocorrection($activity, $activityLinkUser, unserialize($activityLinkUser->getResponse()), false)[1]; 
                        } else if ($activity->getType() == "quiz") {
                            $errorsArray = $this->manageQuizAutocorrection($activity, $activityLinkUser, unserialize($activityLinkUser->getResponse()), false)[1];
                        } else if ($activity->getType() == "dragAndDrop") {
                            $errorsArray = $this->manageDragAndDropAutocorrection($activity, $activityLinkUser, unserialize($activityLinkUser->getResponse()), false)[1];
                        }

                        return ['success' => $errorsArray];
                    } else {
                        return ["error" => "Activity not found"];
                    }
                } else {
                    return ["error" => "Activity not found"];
                }

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                $isRegular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);

                

                return ["error" => "Activity not found"];
            }
        );
    }

    private function manageFreeAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response, $autocorrect) {
        $solution = unserialize($activity->getSolution());
        $errorsArray = [];

        if (mb_strtolower($solution) == mb_strtolower($response) && $autocorrect) {
            $activityLinkUser->setNote(3);
        } else if ($autocorrect) {
            $activityLinkUser->setNote(0);
            $errorsArray[] = "faux";
        }

        return [$activityLinkUser, $errorsArray];
    }

    private function manageQuizAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response, $autocorrect) {
        $solution = unserialize($activity->getSolution());
        $errorsArray = [];
        $correct = 0;
        $total = 0;
        foreach ($solution as $key => $value) {
            $total++;
            if ($value['isCorrect'] == $response[$key]['isCorrect'] && $value['inputVal'] == $response[$key]['inputVal']) {
                $correct++;
            } else {
                $errorsArray[] = $key;
            }
        }

        if ($correct == $total && $autocorrect) {
            $activityLinkUser->setNote(3);
        } else if ($autocorrect) {
            $activityLinkUser->setNote(0);
        }

        return [$activityLinkUser, $errorsArray];
    }

    private function manageDragAndDropAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response, $autocorrect) {
        $solution = unserialize($activity->getSolution());
        $errorsArray = [];
        $correct = 0;
        $total = 0;

        foreach ($solution as $key => $value) {
            $total++;
            if (mb_strtolower(trim($value)) == mb_strtolower(trim($response[$key]['string']))) {
                $correct++;
            } else {
                $errorsArray[] = $key;
            }
        }

        if ($correct == $total && $autocorrect) {
            $activityLinkUser->setNote(3);
        } else if ($autocorrect) {
            $activityLinkUser->setNote(0);
        }

        return [$activityLinkUser, $errorsArray];
    }

    private function manageFillInAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response, $autocorrect) {
    
        $solution = unserialize($activity->getSolution());
        $errorsArray = [];
        $tolerance = $activity->getTolerance();

        foreach ($solution as $key => $value) {

            $a_first_str = str_split(mb_strtolower(trim($response[$key])));
            $a_second_str = str_split(mb_strtolower(trim($value)));
            
            $diff=array_diff_assoc($a_second_str, $a_first_str);

            if (count($diff) > $tolerance) {
                $errorsArray[] = $key;
            }
        }

        if (count($errorsArray) == 0 && $autocorrect) {
            $activityLinkUser->setNote(3);
        } else if ($autocorrect) {
            $activityLinkUser->setNote(0);
        }

        return [$activityLinkUser, $errorsArray];
    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
