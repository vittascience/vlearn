<?php

namespace Learn\Controller;

use User\Entity\Regular;
use Learn\Entity\Activity;
use Database\DataBaseManager;
use Classroom\Entity\ActivityRestrictions;
use Classroom\Entity\UsersLinkApplications;
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
            'get' => function ($data) {
                return $this->entityManager->getRepository(Activity::class)
                    ->find($data['id']);
            },
            'delete' => function ($data) {

                // This function can be accessed by post method only
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $Activity_id = htmlspecialchars($data['id']);
                $activity = $this->entityManager->getRepository(Activity::class)->find($Activity_id);

                $creator_id = $activity->getUser();
                $requester_id = $_SESSION['id'];

                $Allowed = $this->isAllowed($creator_id, $requester_id);

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
     */
    private function isActivitiesLimited(String $activity_id): array
    {
        if (!empty(htmlspecialchars($activity_id))) {

            include_once(__DIR__ . "/../../../../../default-restrictions/constants.php");
            $Restrictions = [];
            $Activities = [];
            $user_id = $_SESSION['id'];

            // get the default restrictions
            if (!empty(activitiesRestrictions)) {
                $Restrictions = activitiesRestrictions;
            }

            // get the actual activity
            $Activity = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $activity_id]);

            if ($Activity) {
                $activity_type = $Activity->getType();
                // Only check if the activity have a type
                if (!empty($Activity->getType())) {
                    $myActivities = $this->entityManager->getRepository(Activity::class)->findBy(["user" => $this->user]);
                    $Applications = $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['user' => $user_id]);
                    $ApplicationFromGroup = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['user' => $user_id]);

                    // Get all the restrictions from his applications
                    if ($Applications) {
                        foreach ($Applications as $application) {
                            $applicationRestrictions = $this->entityManager->getRepository(ActivityRestrictions::class)->findBy(['application' => $$application->getId()]);
                            if ($applicationRestrictions) {
                                if (array_key_exists($applicationRestrictions->getActivityType(), $Restrictions)) {
                                    if ($Restrictions[$applicationRestrictions->getActivityType()] < $applicationRestrictions->getMaxPerTeachers()) {
                                        $Restrictions[$applicationRestrictions->getActivityType()] = $applicationRestrictions->getMaxPerTeachers();
                                    }
                                } else {
                                    $Restrictions[$applicationRestrictions->getActivityType()] = $applicationRestrictions->getMaxPerTeachers();
                                }
                            }
                        }
                    }

                    // Get all the restrictions from his group's applications
                    if ($ApplicationFromGroup) {
                        foreach ($ApplicationFromGroup as $applicationFromGroup) {
                            $applicationRestrictionsFromGroup = $this->entityManager->getRepository(ActivityRestrictions::class)->findBy(['application' => $$application->getId()]);
                            if ($applicationRestrictionsFromGroup) {
                                if (array_key_exists($applicationRestrictionsFromGroup->getActivityType(), $Restrictions)) {
                                    if ($Restrictions[$applicationRestrictionsFromGroup->getActivityType()] < $applicationRestrictionsFromGroup->getMaxPerTeachers()) {
                                        $Restrictions[$applicationRestrictionsFromGroup->getActivityType()] = $applicationRestrictionsFromGroup->getMaxPerTeachers();
                                    }
                                } else {
                                    $Restrictions[$applicationRestrictionsFromGroup->getActivityType()] = $applicationRestrictionsFromGroup->getMaxPerTeachers();
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

                    if ($Activities[$activity_type] >= $Restrictions[$activity_type]) {
                        return ['Limited' => true];
                    } else {
                        return ['Limited' => false];
                    }
                } else {
                    return ['Limited' => false];
                }
            }
        } else {
            return ['Limited' => false];
        }
    }
}
