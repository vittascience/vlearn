<?php

namespace Learn\Controller;


use User\Entity\Regular;
use Learn\Entity\Activity;
use Database\DataBaseManager;
use Classroom\Entity\Classroom;
use Learn\Controller\Controller;
use Classroom\Entity\Applications;
use Classroom\Entity\ActivityLinkClassroom;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\GroupsLinkApplications;
use Classroom\Entity\UsersLinkApplicationsFromGroups;

/* require_once(__DIR__ . '/../../../utils/resize_img.php'); */

class ControllerActivity extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(

            'get_mine' => function () {
                return $this->entityManager->getRepository(Activity::class)->findBy(array("user" => $this->user));
            },
            'get_mine_for_classroom' => function () {
                return $this->entityManager->getRepository(Activity::class)
                    ->findBy(
                        array("user" => $this->user, "isFromClassroom" => true)
                    );
            },
            'get' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getNotRetrievedNotAuthenticated"];

                // bind and sanitize incoming data
                $activityId = !empty($_POST['id']) ? intval($_POST['id']) : null;
                $classroomLink = !empty($_POST['classroomLink']) ? htmlspecialchars((strip_tags(trim($_POST['classroomLink'])))) :'';
                $reference = !empty($_POST['reference']) ? htmlspecialchars(strip_tags(trim($_POST['reference']))) : '';

                // check for errors
                $errors = [];
                if(empty($activityId)) array_push($errors, array('errorType'=>'InvalidId'));

                // some errors found, return them
                if(!empty($errors)) return array('errors'=> $errors);
                
                //get the activity and convert it to a php object
                $activity = $this->entityManager->getRepository(Activity::class)
                    ->find($activityId);
                $activityToSend = json_decode(json_encode($activity));
                
                // a classroomLink is provided to know if the activity is retro attributed to all new students
                if(!empty($classroomLink)){
                    $classroom = $this->entityManager->getRepository(Classroom::class)
                        ->findByLink($classroomLink);
                    
                    // check whether or not the activity appear in classroom_activities_link_classroom table
                    $activityRetroAttributed = $this->entityManager->getRepository(ActivityLinkClassroom::class)->findOneBy(array(
                        'activity'=> $activity,
                        'classroom' => $classroom,
                        "reference" => $reference
                    ));

                    // compute and bind the new property
                    $isRetroAttributed = $activityRetroAttributed ? true : false;
                    $activityToSend->isRetroAttributed = $isRetroAttributed;
                }
                   
                return $activityToSend;
            },
            'delete' => function ($data) {

                // This function can be accessed by post method only
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $Activity_id = (int)htmlspecialchars($data['id']);
                $activity = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $Activity_id]);

                $creator_id = $activity->getUser();
                $requester_id = $_SESSION['id'];

                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                $name = ["name" => "unknow", "id" => "unknow", "message" => "notAllowed"];
                if ($activity && $Allowed) {
                    $this->entityManager->remove($activity);
                    $this->entityManager->flush();
                    $name = ["name" => $activity->getTitle(), "id" => $activity->getId()];
                }
                return $name;
            },
            'add' => function () {

                // bind and sanitize incoming data
                $title = isset($_POST['title'])
                    ? trim(htmlspecialchars(preg_replace('/<[^>]*>[^<]*<[^>]*>/', '', $_POST['title'])))
                    : '';
                $content = isset($_POST['content'])
                    ? trim(htmlspecialchars(preg_replace('/<[^>]*>[^<]*<[^>]*>/', '', $_POST['content'])))
                    : '';
                $isFromClassroom = isset($_POST['isFromClassroom']) && is_bool($_POST['isFromClassroom'])
                    ? $_POST['isFromClassroom']
                    : null;
                $userId = isset($this->user['id'])
                    ? intval($this->user['id'])
                    : null;
                $forkedActivityId = isset($_POST['id']) ? intval($_POST['id']) : null;
                $type = isset($_POST['type']) ? htmlspecialchars(strip_tags(trim($_POST['type']))) : null;
                if (isset($_POST['isFromClassroom']) && filter_var($_POST['isFromClassroom'], FILTER_VALIDATE_BOOLEAN)) {
                    $isFromClassroom = $_POST['isFromClassroom']
                        ? (bool) $_POST['isFromClassroom']
                        : null;
                }

                // create empty errors array and check for errors
                $errors = [];
                if (empty($title)) $errors['activityTitleInvalid'] = true;
                if (empty($content)) $errors['activityContentInvalid'] = true;
                if (empty($isFromClassroom)) $errors['activityIsFromClassroomInvalid'] = true;
                if ($userId == null) $errors['activityUserIdInvalid'] = true;

                // return errors if any
                if (!empty($errors)) {
                    return array(
                        'errors' => $errors
                    );
                }

                // no errors found, we can process the data
                // get the user 
                $user = $this->entityManager
                    ->getRepository('User\Entity\User')
                    ->find($userId);

                if (!$user) {
                    // return an error if the user was not found
                    return array(
                        'error' => 'userNotExists'
                    );
                }


                // create the activity
                $activity = new Activity($title, $content, $user, $isFromClassroom);
                $activity->setType($type);

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
            'isActivitiesLimited' => function ($data) {
                $activityId = htmlspecialchars($data['activityId']);
                $activityType = htmlspecialchars($data['activityType']);
                return $this->isActivitiesLimited($activityId, $activityType);
            }
        );
    }

    private function isAllowed(Int $creator_id, Int $requester_id)
    {
        $Allowed = false;
        $regular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $requester_id]);

        if ($regular) {
            if ($regular->getIsAdmin()) {
                $Allowed = true;
            }
        }

        if ($creator_id == $requester_id) {
            $Allowed = true;
        }

        return $Allowed;
    }

    /**
     * Return the limit status (true if limited)
     * @var $activity_id
     * @return Array
     * // -1 : no limit
     */
    private function isActivitiesLimited(String $activity_id = null, String $activity_type = null): array
    {
        if (!empty(($activity_id)) || !empty(($activity_type))) {

            $activity_id = htmlspecialchars($activity_id);
            $activity_type = htmlspecialchars($activity_type);

            $Restrictions = [];
            $Activities = [];
            $user_id = $_SESSION['id'];

            // Get the default user restrictions in the database and set it in parameters
            //$activitiesDefaultRestrictions = $this->entityManager->getRepository(Restrictions::class)->findOneBy(['name' => "activitiesDefaultRestrictions"]);
            //$activitiesRestrictions = (array)json_decode($activitiesDefaultRestrictions->getRestrictions());

            if (!empty($activitiesRestrictions)) {
                $Restrictions = $activitiesRestrictions;
            }

            
            // get the actual activity
            $Activity = $this->entityManager->getRepository(Activity::class)->findOneBy(['id' => $activity_id]);


            if ($Activity || $activity_type) {
                if (empty($activity_type)) {
                    $activity_type = $Activity->getType();
                }
                // Only check if the activity have a type
                if ($activity_type) {
                    $myActivities = $this->entityManager->getRepository(Activity::class)->findBy(["user" => $this->user]);
                    $UsersApplications = $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['user' => $user_id]);
                    $GroupsApplications = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['user' => $user_id]);

                    // Get all the restrictions from his applications
                    if ($UsersApplications) {
                        foreach ($UsersApplications as $application) {
                            $applicationRestrictions = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $application->getApplication()]);
                            if ($applicationRestrictions) {
                                if (array_key_exists($applicationRestrictions->getName(), $Restrictions)) {
                                    if ($Restrictions[$applicationRestrictions->getName()] < $application->getmaxActivitiesPerTeachers()) {
                                        $Restrictions[$applicationRestrictions->getName()] = $application->getmaxActivitiesPerTeachers();
                                    }
                                } else {
                                    $Restrictions[$applicationRestrictions->getName()] = $application->getmaxActivitiesPerTeachers();
                                }
                            }
                        }
                    } else {
                        $ActivityRestrictionsDefault = $this->entityManager->getRepository(Applications::class)->findOneBy(['name' => $activity_type]);
                        if ($ActivityRestrictionsDefault) {
                            $Restrictions[$activity_type] = $ActivityRestrictionsDefault->getMaxPerTeachers();
                        }
                    }

                    // Get all the restrictions from his group's applications
                    if ($GroupsApplications) {
                        foreach ($GroupsApplications as $applicationFromGroup) {
                            $App = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $applicationFromGroup->getApplication()]);
                            $applicationRestrictionsFromGroup = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $applicationFromGroup->getGroup(), 'application' => $applicationFromGroup->getApplication()]);
                            if ($applicationRestrictionsFromGroup) {
                                if (array_key_exists($App->getName(), $Restrictions)) {
                                    if ($Restrictions[$App->getName()] < $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers()) {
                                        $Restrictions[$App->getName()] = $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers();
                                    }
                                } else {
                                    $Restrictions[$App->getName()] = $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers();
                                }
                            }
                        }
                    }


                    // Sort the activities by type and count them
                    foreach ($myActivities as $activity) {
                        if (array_key_exists($activity->getType(), $Activities)) {
                            $Activities[$activity->getType()]++;
                        } else {
                            $Activities[$activity->getType()] = 1;
                        }
                    }
                    
                    if (array_key_exists($activity_type, $Restrictions)) {
                        if ($Restrictions[$activity_type] == -1) {
                            return ['Limited' => false, 'Restrictions' => $Restrictions];
                        } else {
                            if (array_key_exists($activity_type, $Activities)) {
                                if ($Restrictions[$activity_type] <= $Activities[$activity_type]) {
                                    return ['Limited' => true, 'Restrictions' => $Restrictions, 'ActualActivity' => $Activities[$activity_type]];
                                } else {
                                    return ['Limited' => false, 'Restrictions' => $Restrictions, 'ActualActivity' => $Activities[$activity_type]];
                                }
                            } else {
                                return ['Limited' => false, 'Restrictions' => $Restrictions, 'ActualActivity' => 'none'];
                            }
                        }
                    } else {
                        return ['Limited' => false, 'Restrictions' => $Restrictions];
                    }
                } else {
                    return ['Limited' => false];
                }
            } else {
                return ['missing data' => false];
            }
        } else {
            return ['missing data' => false];
        }
    }

}

