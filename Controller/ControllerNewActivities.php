<?php

namespace Learn\Controller;

use Learn\Entity\Tag;
use User\Entity\User;
use Learn\Entity\Course;
use User\Entity\Regular;
use Learn\Entity\Folders;
use Learn\Entity\Activity;
use Classroom\Entity\Groups;
use Learn\Controller\Controller;
use Learn\Entity\ActivityLinkTag;
use Classroom\Entity\Applications;
use Learn\Entity\CourseLinkActivity;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\UsersRestrictions;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\GroupsLinkApplications;
use Classroom\Entity\UsersLinkApplicationsFromGroups;

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
                $content = !empty($data['content']) ? $data['content'] : null;
                $solution = !empty($data['solution']) ? $data['solution'] : null;
                $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;
                $folderId = !empty($data['folder']) ? htmlspecialchars($data['folder']) : null;
                $tagsList = !empty($data['tags']) ? $data['tags'] : null;

                $regular = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $this->user['id']]);

                $exercice = new Activity($title, $content, $regular, true);

                if ($solution) {
                    $exercice->setSolution($solution);
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


                $this->manageTagsForActivities($exercice, $tagsList);
                

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

                        // Delete this activity from all the courses
                        $courseLinkActivity = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['activity' => $activity]);
                        if ($courseLinkActivity) {
                            foreach ($courseLinkActivity as $cla) {
                                $this->entityManager->remove($cla);
                            }
                        }

                        // delete all tags 
                        $this->removeTagsForActivity($activity);


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
                        $content = !empty($data['content']) ? $data['content'] : null;
                        $solution = !empty($data['solution']) ? $data['solution'] : null;
                        $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                        $autocorrect = !empty($data['autocorrect']) ? htmlspecialchars($data['autocorrect']) : null;
                        $tagsList = !empty($data['tags']) ? $data['tags'] : null;

                        $activity->setTitle($title);
                        $activity->setType($type);
                        $activity->setContent($content);

                        if ($solution) {
                            $activity->setSolution($solution);
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

                        $this->manageTagsForActivities($activity, $tagsList);
                        

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


                $optionalData = !empty($_POST['optionalData']) ? $_POST['optionalData'] : null;

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

                $content = $this->manageUnserialize($acti->getContent());

                $hint = "";
                if (is_array($content)) {
                    if (array_key_exists('hint', $content)) {
                        $hint = $content['hint'];
                    }
                } else if (is_string($content)) {
                    $decodedContent = json_decode($content);
                    if (property_exists($decodedContent, 'hint')) {
                        $hint = $decodedContent->hint;
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

                    $activity->setResponse($response);
                    //dd($response);
                    $response = $this->manageJsonDecode($response);
                    if ($optionalData) {
                        $activity->setOptionalData($optionalData);
                    }
    
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
                        return ['success' => true, 'message' => "activitySaved", 'id' => $activityId, 'link' => $activityLinkId];
                    }

                    if (count($errorsArray) > 0 && $activity->getEvaluation() != 1) {
                        if (empty($response)) {
                            return ['success'=> false, 'message' => 'emptyAnswer', 'id' => $activityId, 'link' => $activityLinkId];
                        }

                        return ['badResponse' => $errorsArray, 'hint' => $hint, 'id' => $activityId, 'link' => $activityLinkId];
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

                    $content = $this->manageLtiContentForDuplicate($activity);
                    $duplicatedActivity = new Activity($newTitle, $content, $activity->getUser(), $activity->isFromClassroom());


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

                    $duplicatedActivity->setFork($activity);

                    if ($activity->getFolder() != null) {
                        $duplicatedActivity->setFolder($activity->getFolder());
                    }

                    $this->entityManager->persist($duplicatedActivity);

                    $this->copyTagsForDuplicate($activity, $duplicatedActivity);

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
                    $response = $this->manageUnserialize($this->manageJsonDecode($activityLinkUser->getResponse()));
                
                    if ($activity && $activityLinkUser) {
                        $errorsArray = [];
                        if ($activity->getType() == "fillIn") {
                            $errorsArray = $this->manageFillInAutocorrection($activity, $activityLinkUser, $response, false)[1]; 
                        } else if ($activity->getType() == "quiz") {
                            $errorsArray = $this->manageQuizAutocorrection($activity, $activityLinkUser, $response, false)[1];
                        } else if ($activity->getType() == "dragAndDrop") {
                            $errorsArray = $this->manageDragAndDropAutocorrection($activity, $activityLinkUser, $response, false)[1];
                        }

                        return ['success' => $errorsArray];
                    } else {
                        return ["error" => "Activity not found"];
                    }
                } else {
                    return ["error" => "Activity not found"];
                }

                return ["error" => "Activity not found"];
            },
            "import_ressource" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                // get id activity and id activity link user
                $ressourceId = !empty($_POST['ressource_id']) ? intval($_POST['ressource_id']) : 0;
                $ressourceType = !empty($_POST['ressource_type']) ? ($_POST['ressource_type']) : null;

                if (empty($ressourceType)) {
                    return ["error" => "Ressource type not found"];
                } 

                if (empty($ressourceId)) {
                    return ["error" => "Ressource id not found"];
                }

                if ($ressourceType == "activity") {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($ressourceId);
                    // duplicate with new user
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                    $activityDuplicated = new Activity($activity->getTitle(),  
                                                        $activity->getContent(), 
                                                        $user, 
                                                        $activity->isFromClassroom());        
                                                        
                    if ($activity->getType()) {
                        $activityDuplicated->setType($activity->getType());
                    }
                    if ($activity->getSolution()) {
                        $activityDuplicated->setSolution($activity->getSolution());
                    }
                    if ($activity->getTolerance()) {
                        $activityDuplicated->setTolerance($activity->getTolerance());
                    }
                    if ($activity->getIsAutocorrect()) {
                        $activityDuplicated->setIsAutocorrect($activity->getIsAutocorrect());
                    }

                    // 
                    $activityDuplicated->setFork($activity);

                    $this->entityManager->persist($activityDuplicated);

                    $this->copyTagsForDuplicate($activity, $activityDuplicated);

                    $this->entityManager->flush();
                    return  ['success' => true, 'id' => $activityDuplicated->getId()];
                } else if ($ressourceType == "course") {
                    
                    $course = $this->entityManager->getRepository(Course::class)->find(["id" => $ressourceId]);

                    if (!$course) return ["error" => "course not found"];

                    $user = $this->entityManager->getRepository(User::class)->find(["id" => $_SESSION['id']]);

                    // get course link activities
                    $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);


                    $courseDuplicate = new Course();
                    $courseDuplicate->setTitle($course->getTitle());
                    $courseDuplicate->setDescription($course->getDescription());
                    //$course->setImg($image);
                    $courseDuplicate->setDuration($course->getDuration());
                    $courseDuplicate->setDifficulty($course->getDifficulty());
                    $courseDuplicate->setLang($course->getLang());
                    $courseDuplicate->setUser($user);
                    $courseDuplicate->setFork($course);
                    $courseDuplicate->setRights($course->getRights());
                    $courseDuplicate->setDeleted(false);
                    $courseDuplicate->setFolder(null);
                    $courseDuplicate->setFormat($course->getFormat());
                    $this->entityManager->persist($courseDuplicate);
                    $this->entityManager->flush();

                    foreach ($courseLinkActivities as $key => $cla) {
                        $result = $this->importRessource($cla->getActivity()->getId());
                        if ($result['success'] == false) return $result;
                        $courseLinkActivity = new CourseLinkActivity($courseDuplicate, $result['activity'], $cla->getIndexOrder());
                        $this->entityManager->persist($courseLinkActivity);
                        $this->entityManager->flush();
                    }


                    $activities = [];
                    foreach ($courseLinkActivities as $cla) {
                        $activities[] = $cla->getActivity();
                    }

                    $courseSerialized = $courseDuplicate->jsonSerialize();
                    $courseSerialized['activities'] = $activities;

                    return ["success" => true, "course" => $courseSerialized];
                }
            },
            "create_new_tag" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                $tagName = !empty($_POST['tag_name']) ? ($_POST['tag_name']) : null;
                // sanitize
                $tagName = htmlspecialchars($tagName);

                if (empty($tagName)) {
                    return ["error" => "Tag name not found"];
                }

                $tag = new Tag($tagName);
                $this->entityManager->persist($tag);
                $this->entityManager->flush();
                
                return ['success' => true, 'id' => $tag->getId(), 'name' => $tag->getName()];
            },
            "update_tag" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                $tagId = !empty($_POST['tag_id']) ? ($_POST['tag_id']) : null;
                $tagName = !empty($_POST['tag_name']) ? ($_POST['tag_name']) : null;
                // sanitize 
                $tagId = htmlspecialchars($tagId);
                $tagName = htmlspecialchars($tagName);

                if (empty($tagId)) {
                    return ["error" => "Tag id not found"];
                }

                if (empty($tagName)) {
                    return ["error" => "Tag name not found"];
                }

                $tag = $this->entityManager->getRepository(Tag::class)->find($tagId);
                if ($tag) {
                    $tag->setName($tagName);
                    $this->entityManager->persist($tag);
                    $this->entityManager->flush();
                } else {
                    return ["error" => "Tag not found"];
                }
                
                return ['success' => true, 'id' => $tag->getId(), 'name' => $tag->getName()];

                return ["error" => "Activity not found"];
            },
            "delete_tag" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];


                $tagId = !empty($_POST['tag_id']) ? ($_POST['tag_id']) : null;
                // sanitize
                $tagId = htmlspecialchars($tagId);

                if (empty($tagId)) {
                    return ["error" => "Tag id not found"];
                }

                $tag = $this->entityManager->getRepository(Tag::class)->find($tagId);
                if ($tag) {
                    $this->entityManager->remove($tag);
                    $this->entityManager->flush();
                } else {
                    return ["error" => "Tag not found"];
                }
                
                return ['success' => true];

                return ["error" => "Activity not found"];
            }, 
            "get_all_tags" => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                $tags = $this->entityManager->getRepository(Tag::class)->findAll();
                $tagsArray = [];
                foreach ($tags as $tag) {
                    $tagsArray[] = $tag->jsonSerialize();
                }
                return ['success' => true, 'tags' => $tagsArray];
            },
        );
    }


    public function manageTagsForActivities(Activity $activity, ?array $idTagList) {
        $activityLinkTags = $this->entityManager->getRepository(ActivityLinkTag::class)->findBy(['activity' => $activity]);
        foreach ($activityLinkTags as $activityLinkTag) {
            if (empty($idTagList)) {
                $this->entityManager->remove($activityLinkTag);
                continue;
            }
            if (!in_array($activityLinkTag->getTag()->getId(), $idTagList)) {
                $this->entityManager->remove($activityLinkTag);
            }
        }
        $this->entityManager->flush();

        if (empty($idTagList)) {
            return;
        }
        
        foreach ($idTagList as $idTag) {
            $tag = $this->entityManager->getRepository(Tag::class)->find($idTag);
            if ($tag) {
                $activityLinkTag = $this->entityManager->getRepository(ActivityLinkTag::class)->findOneBy(['activity' => $activity, 'tag' => $tag]);
                if ($activityLinkTag) {
                    continue;
                }
                $activityLinkTag = new ActivityLinkTag($activity, $tag);
                $this->entityManager->persist($activityLinkTag);
            }
        }
        $this->entityManager->flush();
    }

    public function copyTagsForDuplicate(Activity $activity, Activity $activityDuplicated) {
        $activityLinkTags = $this->entityManager->getRepository(ActivityLinkTag::class)->findBy(['activity' => $activity]);
        foreach ($activityLinkTags as $activityLinkTag) {
            $activityLinkTagDuplicated = new ActivityLinkTag($activityDuplicated, $activityLinkTag->getTag());
            $this->entityManager->persist($activityLinkTagDuplicated);
        }
        $this->entityManager->flush();
    }

    public function removeTagsForActivity(Activity $activity) {
        $activityLinkTags = $this->entityManager->getRepository(ActivityLinkTag::class)->findBy(['activity' => $activity]);
        foreach ($activityLinkTags as $activityLinkTag) {
            $this->entityManager->remove($activityLinkTag);
        }
        $this->entityManager->flush();
    }

    private function manageLtiContentForDuplicate(Activity $activity): ?string {
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
        $content = $this->manageUnserialize($activity->getContent());
        if (gettype($content) == "array") {
            $content = json_encode($content);
        }
        if ($isLti) {
            $content = json_decode($content, true);
            if (!str_contains($content["description"], "&duplicate=1")) {
                $content["description"] .= "&duplicate=1";
            }
            $content = json_encode($content);
        }
        return $content;
    }

    private function importRessource(int $Id) {
        $activity = $this->entityManager->getRepository(Activity::class)->find($Id);
        // duplicate with new user
        
        $content = $this->manageLtiContentForDuplicate($activity);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
        $activityDuplicated = new Activity($activity->getTitle(), $content, $user, $activity->isFromClassroom());
                                            
        if ($activity->getType()) {
            $activityDuplicated->setType($activity->getType());
        }
        if ($activity->getSolution()) {
            $activityDuplicated->setSolution($activity->getSolution());
        }
        if ($activity->getTolerance()) {
            $activityDuplicated->setTolerance($activity->getTolerance());
        }
        if ($activity->getIsAutocorrect()) {
            $activityDuplicated->setIsAutocorrect($activity->getIsAutocorrect());
        }
        $activityDuplicated->setFork($activity);

        $this->entityManager->persist($activityDuplicated);
        $this->copyTagsForDuplicate($activity, $activityDuplicated);
        $this->entityManager->flush();
        return  ['success' => true, 'activity' => $activityDuplicated];
    }

    private function manageFreeAutocorrection(Activity $activity, ActivityLinkUser $activityLinkUser, $response, $autocorrect) {
        $solution = $this->manageUnserialize($this->manageJsonDecode($activity->getSolution()));
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
        $solution = $this->manageUnserialize($this->manageJsonDecode($activity->getSolution()));
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
        $solution = $this->manageUnserialize($this->manageJsonDecode($activity->getSolution()));
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
        $solution = $this->manageUnserialize($this->manageJsonDecode($activity->getSolution()));
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

    private function manageUnserialize($string) {
        if (gettype($string) == "array" || @unserialize($string) == false) {
            return $string;
        } else {
            return unserialize($string);
        }
    }

    private function manageJsonDecode($string) {
        if ($this->isJson($string)) {
            return json_decode($string, true);
        } else {
            return $string;
        }
    }
}
