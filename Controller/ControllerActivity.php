<?php

namespace Learn\Controller;

use Learn\Entity\Tag;
use User\Entity\User;
use User\Entity\Regular;
use Learn\Entity\Folders;
use Learn\Entity\Activity;
use Classroom\Entity\Groups;
use Database\DataBaseManager;
use Classroom\Entity\Classroom;
use Learn\Controller\Controller;
use Learn\Entity\ActivityLinkTag;
use Classroom\Entity\Applications;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\UsersRestrictions;
use Classroom\Entity\ActivityRestrictions;
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
            'get_mine_for_classroom' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
 
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getMineForClassroomNotAuthenticated"];
 
                $userId = intval($_SESSION['id']);
 
                // get the user
                $user = $this->entityManager->getRepository(User::class)->find($userId);
 
                // check for errors
                $errors = [];
 
                // user not found return an error
                if(!$user) array_push($errors, array('errorType'=>'userNotFound'));
                if(!empty($errors)) return array('errors'=> $errors);
 
                $activities = $this->entityManager
                    ->getRepository(Activity::class)
                    ->findBy(
                        array("user" => $user, "isFromClassroom" => true)
                    );
 
                $activitiesToSend = json_decode(json_encode($activities));
 
                foreach($activitiesToSend as $activity){
                     // get the activity restriction by type
                     $application = $this->entityManager
                     ->getRepository(Applications::class)
                     ->findOneBy(array(
                         'name'=> $activity->type
                     ));
                    
                     // bind isLti property to $dataToSend
                     $activity->isLti = $application
                     ? $application->getIsLti()
                     : false;

                    // get the tags of the activity
                    $tags = $this->entityManager
                    ->getRepository(ActivityLinkTag::class)
                    ->findBy(['activity' => $activity->id]);

                    if ($tags) {
                        $tagsId = [];
                        foreach($tags as $tag){
                            array_push($tagsId, $tag->getTag()->getId());
                        }
                        $activity->tags = $tagsId;
                    }
                }
                
                return $activitiesToSend;
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

                // get the tags of the activity
                $tags = $this->entityManager
                ->getRepository(ActivityLinkTag::class)
                ->findBy(['activity' => $activity->getId()]);

                if ($tags) {
                    $tagsId = [];
                    foreach($tags as $tag){
                        array_push($tagsId, $tag->getTag()->getId());
                    }
                    $activityToSend->tags = $tagsId;
                }
                
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
                    // Update Rémi (delete all link between activity and user)
                    $activityLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(["activity" => $Activity_id]);
                    foreach ($activityLinkUser as $activityLink) {
                        $this->entityManager->remove($activityLink);
                    }
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
                    ? trim(preg_replace('/<[^>]*>[^<]*<[^>]*>/', '', $_POST['content']))
                    : '';

                // decode the content if needed
                if(preg_match("/&quot;/i",$content,$matches)) $content = htmlspecialchars_decode($content);

                // encode/re-encode the whole content
                $content = htmlspecialchars($content);
           
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
            },
            'moveActiToFolder'  => function ($data) {

                $activityId = htmlspecialchars($data['activityId']);
                $folderId = htmlspecialchars($data['folderId']);

                $activity = $this->entityManager->getRepository(Activity::class)->find($activityId);

                // check if allowed 
                $requester_id = $_SESSION['id'];
                $creator_id = $activity->getUser();
                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                if (!$Allowed) {
                    return array(
                        'error' => 'notAllowed'
                    );
                }

                $folder = $this->entityManager->getRepository(Folders::class)->find($folderId);
                $activity->setFolder($folder);
                $this->entityManager->persist($activity);
                $this->entityManager->flush();

                return [
                    'success' => true,
                    'activity' => $activity,
                    'folder' => $folder
                ];
            },
            'moveFolderToFolder'  => function ($data) {

                $folderId = htmlspecialchars($data['folderId']);
                $destinationFolderId = htmlspecialchars($data['destinationFolderId']);

                $folder = $this->entityManager->getRepository(Folders::class)->find($folderId);
                $destinationFolder = $this->entityManager->getRepository(Folders::class)->find($destinationFolderId);

                // check if allowed 
                $requester_id = $_SESSION['id'];
                $creator_id = $folder->getUser();
                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                if (!$Allowed) {
                    return array(
                        'error' => 'notAllowed'
                    );
                }

                $folder->setParentFolder($destinationFolder);
                $this->entityManager->persist($folder);
                $this->entityManager->flush();

                return [
                    'success' => true,
                    'folder' => $folder,
                ];
            },
            "get_all_user_folders" => function () {
                // get all user's activities
                $user = $this->entityManager->getRepository(User::class)->find($this->user['id']);
                $myFolders = $this->entityManager->getRepository(Folders::class)->findBy(["user" => $user]);
                return $myFolders;
            },
            "create_folder" => function () {
                $name = htmlspecialchars($_POST['name']);
                $parent = htmlspecialchars($_POST['parent']);

                
                if (strlen($name) < 1) {
                    return array(
                        'error' => 'folderNameInvalid'
                    );
                }

                $parentFolder = $this->entityManager->getRepository(Folders::class)->find($parent);
                $user = $this->entityManager->getRepository(User::class)->find($this->user['id']);
                $folder = new Folders($name, $user, $parentFolder);

                $this->entityManager->persist($folder);
                $this->entityManager->flush();

                return $folder;
            },
            "update_folder" => function () {

                $name = htmlspecialchars($_POST['name']);
                $id = htmlspecialchars($_POST['id']);

                $folder = $this->entityManager->getRepository(Folders::class)->find($id);

                // check if allowed 
                $requester_id = $_SESSION['id'];
                $creator_id = $folder->getUser();
                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                if (!$Allowed) {
                    return ['error' => 'notAllowed'];
                }

                $folder->setName($name);

                $this->entityManager->persist($folder);
                $this->entityManager->flush();

                return $folder;
            },
            "delete_folder" => function () {
                $id = htmlspecialchars($_POST['id']);

                $folder = $this->entityManager->getRepository(Folders::class)->find($id);

                // check if allowed 
                $requester_id = $_SESSION['id'];
                $creator_id = $folder->getUser();
                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                if (!$Allowed) {
                    return ['error' => 'notAllowed'];
                }
                
                if ($this->deleteChildren($folder) == false) {
                    return ['error' => 'error'];
                }

                $this->entityManager->remove($folder);
                $this->entityManager->flush();

                return $folder;
            }
            // @ToBeRemoved
            // @Naser no ajax call to this method from the front
            // last check June 2022
            // 'get_mine' => function () {
            //     return $this->entityManager->getRepository(Activity::class)->findBy(array("user" => $this->user));
            // },
        );
    }

    private function deleteChildren($folder) {
        $Childrens = $this->entityManager->getRepository(Folders::class)->findBy(["parentFolder" => $folder]);
        $Activities = $this->entityManager->getRepository(Activity::class)->findBy(["folder" => $folder]);
 
        foreach ($Activities as $activity) {
            $activitiesLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(["activity" => $activity->getId()]);
            foreach ($activitiesLinkUser as $activityLinkUser) {
                $this->entityManager->remove($activityLinkUser);
            }
            $this->entityManager->remove($activity);
        }

        foreach ($Childrens as $child) {
            $this->deleteChildren($child);
            $this->entityManager->remove($child);
        }
        
        return true;
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


            // get the actual activity
            $Activity = $this->entityManager->getRepository(Activity::class)->findOneBy(['id' => $activity_id]);


            if ($Activity || $activity_type) {
                if (empty($activity_type)) {
                    $activity_type = $Activity->getType();
                }
                // Only check if the activity have a type

                if (!$activity_type) {
                    // no limit if no type
                    return ['Limited' => false];
                }

                $foundApp = $this->entityManager->getRepository(Applications::class)->findOneBy(['name' => $activity_type]);

                if (!$foundApp) {
                    // no limit if no app
                    return ['Limited' => false];
                }

                // Set the base restriction
                $Restrictions[$foundApp->getName()] = $foundApp->getMaxPerTeachers();

                $myActivities = $this->entityManager->getRepository(Activity::class)->findBy(["user" => $this->user, "type" => $activity_type]);
                $UsersApplication = $this->entityManager->getRepository(UsersLinkApplications::class)->findOneBy(['user' => $user_id, 'application' => $foundApp->getId()]);
                $GroupsApplication = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findOneBy(['user' => $user_id, 'application' => $foundApp->getId()]);

                // Get all the restrictions from his applications
                if ($UsersApplication) {
                    if (array_key_exists($foundApp->getName(), $Restrictions)) {
                        if (($Restrictions[$foundApp->getName()] < $UsersApplication->getmaxActivitiesPerTeachers() && $Restrictions[$foundApp->getName()] != -1) 
                        || $UsersApplication->getmaxActivitiesPerTeachers() == -1) {
                            $Restrictions[$foundApp->getName()] = $UsersApplication->getmaxActivitiesPerTeachers();
                        }
                    } else {
                        $Restrictions[$foundApp->getName()] = $UsersApplication->getmaxActivitiesPerTeachers();
                    }
                } else {
                    $Restrictions[$activity_type] = $foundApp->getMaxPerTeachers();
                }

                // Get all the restrictions from his group's applications
                if ($GroupsApplication) {
                    if ($this->getAvailableActivitiesFromGroup($GroupsApplication)) {
                        $applicationRestrictionsFromGroup = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $GroupsApplication->getGroup(), 'application' => $GroupsApplication->getApplication()]);    
                        if (array_key_exists($foundApp->getName(), $Restrictions)) {
                            if (($Restrictions[$foundApp->getName()] < $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers() && $Restrictions[$foundApp->getName()] != -1) 
                            || $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers() == -1) {
                                $Restrictions[$foundApp->getName()] = $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers();
                            }
                        } else {
                            $Restrictions[$foundApp->getName()] = $applicationRestrictionsFromGroup->getmaxActivitiesPerTeachers();
                            $Restrictions['message'] = 'Default limit from group';
                        }
                    } else {
                        $Restrictions['message'] = 'Group limit reached';
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
                // wrong activity type
                return ['missing data' => false];
            }
        } else {
            // no activity id or type provided
            return ['missing data' => false];
        }
    }


    private function getAvailableActivitiesFromGroup(UsersLinkApplicationsFromGroups $Ulafg) {
        $group = $Ulafg->getGroup(); 
        $application = $Ulafg->getApplication();
        // Get group link application
        $groupLinkApplication = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $group, 'application' => $application]);
        $maxActivitiesPerGroup = $groupLinkApplication->getmaxActivitiesPerGroups();


        // Get all users from the group
        $users = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['group' => $group]);
        //for each user get all his activities
        $totalActivities = 0;
        foreach ($users as $user) {
            $activities = $this->entityManager->getRepository(Activity::class)->findBy(['user' => $user->getUser()]);
            $userTotalActivities = 0;
            foreach ($activities as $activity) {
                if ($activity->getType() == $application->getName()) {
                    $userTotalActivities++;
                }
            }

            // get personal application 
            $personalApplication = $this->entityManager->getRepository(UsersLinkApplications::class)->findOneBy(['user' => $user->getUser(), 'application' => $application]);
            if ($personalApplication) {
                $ownMax = $personalApplication->getmaxActivitiesPerTeachers();

                if ($ownMax == -1) {
                    $totalActivities = 0;
                } else if ($ownMax > 0) {
                    $toAdd = $ownMax - $userTotalActivities;
                    if ($toAdd > 0) {
                        $totalActivities += $toAdd;
                    }
                } else {
                    $totalActivities += $userTotalActivities;
                }
            } else {
                $totalActivities += $userTotalActivities;
            }

        }

        // if the max is -1 it's unlimited
        if ($maxActivitiesPerGroup == -1) {
            return true;
        } else if ($maxActivitiesPerGroup >= 0) {
            // if the total of available activities is greater than 0 it's ok
            $activitiesAvailable = $maxActivitiesPerGroup - $totalActivities;
            if ($activitiesAvailable > 0) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }   
}   

