<?php

namespace Learn\Controller;

use User\Entity\User;
use Learn\Entity\Course;
use Learn\Entity\Lesson;
use User\Entity\Regular;
use Learn\Entity\Folders;
use Learn\Entity\Activity;
use Learn\Entity\CourseLinkCourse;
use Classroom\Entity\CourseLinkUser;
use Learn\Entity\CourseLinkActivity;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;

class ControllerCourse extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_one' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind and sanitize incoming data
                $tutorialId = intval($_POST['id']);

                // get the tutorial and related activities
                $tutorial = $this->entityManager
                    ->getRepository('Learn\Entity\Course')
                    ->find($tutorialId);

                $courseForksCountAndTree = $this->entityManager->getRepository(Course::class)->getCourseForksCountAndTree($tutorialId);

                $tutorialToReturn = json_decode(json_encode($tutorial));
                $tutorialToReturn->forksCount = intval($courseForksCountAndTree['forksCount']);
                $tutorialToReturn->forksTree = $courseForksCountAndTree['tree'];

                $activities = $this->entityManager
                    ->getRepository('Learn\Entity\CourseLinkActivity')
                    ->getActivitiesOrdered($tutorialId);

                // create empty array to fill with formatted activities
                $arrayActivities = [];
                for ($index = 0; $index < count($activities); $index++) {
                    $arrayActivities[$index] = $activities[$index]->getActivity();
                }

                // prepare data to return
                $tutorial = array(
                    "tutorial" => $tutorialToReturn,
                    "activities" => $arrayActivities
                );
                return $tutorial;
            },
            'get_by_user' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                // bind and sanitize incoming data
                $limit = !empty($_POST['limit']) ? intval($_POST['limit']) : null;
                $userId = intval($_SESSION['id']);

                $user = $this->entityManager->getRepository(User::class)->find($userId);

                if (empty($limit)) {
                    $results =  $this->entityManager
                        ->getRepository('Learn\Entity\Course')
                        ->findBy(
                            array("user" => $user),
                            array('createdAt' => 'DESC') //order
                        );
                } else {
                    // fetch data according to $limit
                    $results =  $this->entityManager
                        ->getRepository('Learn\Entity\Course')
                        ->findBy(
                            array("user" => $user),
                            array('createdAt' => 'DESC'), //order
                            $limit
                        );
                }

                // prepare and return data
                $arrayResult = [];
                foreach ($results as $result) {
                    if (json_encode($result) != NULL && json_encode($result) != false) {
                        $resultToReturn = json_decode(json_encode(($result)));
                        $resultToReturn->forksCount = $this->entityManager->getRepository('Learn\Entity\Course')->getCourseForksCountAndTree($resultToReturn->id)['forksCount'];

                        $arrayResult[] =  $resultToReturn;
                    }
                }
                return $arrayResult;
            },
            'get_by_filter' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind incoming search param
                $search = !empty($_POST['filter']['search'])
                    ? htmlspecialchars(strip_tags(trim(addslashes($_POST['filter']['search']))))
                    : '';
                unset($_POST['filter']['search']);

                $sort = !empty($_POST['filter']['sort'])
                    ? htmlspecialchars(strip_tags(trim($_POST['filter']['sort'][0])))
                    : '';
                unset($_POST['filter']['sort']);


                // bind and/or sanitize other incoming params
                $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
                $sanitizedFilters = $this->sanitizeAndFormatFilterParams($_POST['filter']);

                // fetch data from db 
                $results = $this->entityManager->getRepository('Learn\Entity\Course')->getByFilter($sanitizedFilters, $search, $sort, $page);
                // prepare and return data
                $arrayResult = [];
                foreach ($results as $result) {
                    if (json_encode($result) != NULL && json_encode($result) != false) {
                        $resultToReturn = json_decode(json_encode(($result)));
                        $resultToReturn->forksCount = $this->entityManager->getRepository('Learn\Entity\Course')->getCourseForksCountAndTree($resultToReturn->id)['forksCount'];

                        $arrayResult[] =  $resultToReturn;
                    }
                }
                return $arrayResult;
            },
            'count_by_filter' => function ($data) {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind incoming search param
                $search = !empty($_POST['filter']['search'])
                    ? htmlspecialchars(strip_tags(trim(addslashes($_POST['filter']['search']))))
                    : '';
                unset($_POST['filter']['search']);

                // bind and/or sanitize other incoming params
                $sanitizedFilters = $this->sanitizeAndFormatFilterParams($_POST['filter']);

                // fetch data from db 
                return $this->entityManager->getRepository('Learn\Entity\Course')->countByFilter($sanitizedFilters,  $search);
            },
            'add' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["error" => "Not Authenticated"];

                try {
                    // bind incoming tutorial data
                    $incomingTutorial = $this->bindIncomingTutorialData($_POST);
                    $userId = intval($_SESSION['id']);

                    // check for errors and return them if any
                    $tutorialErrors = $this->validateIncomingTutorialData($incomingTutorial);
                    if (!empty($tutorialErrors)) return array('errors' => $tutorialErrors);

                    // bind and sanitize the remaining data to be inserted in db
                    $linked = [];
                    if (!empty($_POST['linkedTuto'])) {
                        foreach (json_decode($_POST['linkedTuto']) as $incomingLinkedTuto) {
                            array_push($linked, intval($incomingLinkedTuto));
                        }
                    }

                    $chapters = [];
                    if (!empty($_POST['chapters'])) {
                        foreach (json_decode($_POST['chapters']) as $incomingchapter) {
                            array_push($chapters, intval($incomingchapter));
                        }
                    }

                    $tutorialParts = json_decode($_POST['tutorialParts']);
                    // translate first $tutorialPart content from $name and url to full bbcode
                    for ($i = 0; $i < count($tutorialParts); $i++) {

                        if ($i === 0) {
                            // initialize $content to be concatenated with sanitized values later
                            $content = "[fa-list]";

                            // first content property is an array of names and urls
                            $nameAndUrlPairs = $tutorialParts[$i]->content;

                            foreach ($nameAndUrlPairs as $nameAndUrlPair) {
                                // bind and sanitize incoming data
                                $name = htmlspecialchars(strip_tags(trim($nameAndUrlPair->name)));
                                $url = htmlspecialchars(strip_tags(trim($nameAndUrlPair->url)));

                                // fill content with sanitized values
                                $content .= empty($url)
                                    ? "[*][fa-*]{$name}[/*]"
                                    : "[*][fa-url={$url}]{$name}[/url][/*]";
                            }
                            $content .= "[/list]";
                            $tutorialParts[$i]->content = $content;
                            $tutorialParts[$i]->isCollapsed =  false;
                        } else {
                            // bind and sanitize incoming data
                            $title = $tutorialParts[$i]->title;
                            $content = htmlspecialchars(strip_tags(trim($tutorialParts[$i]->content)));
                            $isCollapsed = filter_var($tutorialParts[$i]->isCollapsed, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                            // replace values by the same values but sanitized
                            $tutorialParts[$i]->title =  $title;
                            $tutorialParts[$i]->content =  $content;
                            $tutorialParts[$i]->isCollapsed =  $isCollapsed;
                        }
                    }

                    $forkedFromResourceId = !empty($_POST['forkedFromResourceId']) ? intval($_POST['forkedFromResourceId']) : null;

                    // unset bound data
                    unset($_POST['linkedTuto']);
                    unset($_POST['tutorialParts']);
                    unset($_POST['chapters']);
                    unset($_POST['forkedFromResourceId']);

                    $tutorial = Course::jsonDeserialize($incomingTutorial);

                    if (isset($_FILES['imgFile'])) {
                        $tutorial->setImg($_FILES['imgFile']);
                    }
                    $user = $this->entityManager->getRepository('User\Entity\User')->find($userId);
                    $regular = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(array('user' => $user));
                    $tutorial->setUser($user);
                    $tutorial->setCreatedAt();

                    if ($forkedFromResourceId) {
                        $forkedTutorialFound = $this->entityManager->getRepository(Course::class)->find($forkedFromResourceId);
                        if ($forkedTutorialFound) $tutorial->setFork($forkedTutorialFound);
                    }
                    $this->entityManager->persist($tutorial);

                    //add parts to the tutorial
                    for ($index = 0; $index < count($tutorialParts); $index++) {
                        $activity = new Activity($tutorialParts[$index]->title, $tutorialParts[$index]->content);
                        $activity->setIsCollapsed($tutorialParts[$index]->isCollapsed);
                        $this->entityManager->persist($activity);
                        $courseLinkActivity = new CourseLinkActivity($tutorial, $activity, $index);
                        $this->entityManager->persist($courseLinkActivity);
                    }

                    $this->entityManager->flush();

                    $this->saveLessonsIfNeeded($tutorial, $chapters);
                    $this->saveRelatedTutorialsIfNeeded($tutorial, $linked);

                    $this->sendToMailerlite($incomingTutorial->rights, $tutorial->getId(), $regular->getEmail());
                    
                    return $tutorial;
                } catch (\Error $error) {
                    echo ($error->getMessage());
                }
            },
            'increment_views' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind incoming data
                $tutorialId = !empty($_POST['id']) ? intval($_POST['id']) : null;

                // invalid tutorial id, return an error
                if (empty($tutorialId)) {
                    return array('errors' => array('errorType' => 'tutorialIdInvalid'));
                }

                // tutorial already viewed by the user, do nothing
                if (isset($_SESSION['views'][$tutorialId]))  return false;

                // tutorial not already viewed by the user, increment view count in db
                $_SESSION['views'][$tutorialId] = 1;
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->findOneBy(array("id" => $tutorialId));
                $tutorial->incrementViews();
                $this->entityManager->persist($tutorial);
                $this->entityManager->flush();
                return true;
            },
            'update' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "courseNotUpdatedNotAuthenticated"];

                // bind and sanitize data
                $userId = intval($_SESSION['id']);
                $courseId = intval($_POST['id']);

                // check if user is admin
                $isAdmin = $this->entityManager->getRepository(Regular::class)->findOneBy(array(
                    'user' => $userId,
                    'isAdmin' => true
                ));

                //check if the userr is the owner of the course
                $isOwnerOfCourse = $this->entityManager->getRepository(Course::class)->findOneBy(array(
                    'id' => $courseId,
                    'user' => $userId
                ));

                // user is not admin and not the owner of the course
                if (!$isAdmin && !$isOwnerOfCourse) {
                    return ["errorType" => "courseNotUpdatedNotAuthorized"];
                }

                // bind and sanitize the remaining data to be inserted in db
                $linked = [];
                if (!empty($_POST['linkedTuto'])) {
                    foreach (json_decode($_POST['linkedTuto']) as $incomingLinkedTuto) {
                        array_push($linked, intval($incomingLinkedTuto));
                    }
                }

                $chapters = [];
                if (!empty($_POST['chapters'])) {
                    foreach (json_decode($_POST['chapters']) as $incomingchapter) {
                        array_push($chapters, intval($incomingchapter));
                    }
                }

                $tutorialParts = json_decode($_POST['tutorialParts']);
                // translate first $tutorialPart content from $name and url to full bbcode
                for ($i = 0; $i < count($tutorialParts); $i++) {

                    if ($i === 0) {
                        // initialize values
                        $content = "[fa-list]";
                        $nameAndUrlPairs = $tutorialParts[$i]->content;

                        foreach ($nameAndUrlPairs as $nameAndUrlPair) {
                            // bind and sanitize incoming data
                            $name = htmlspecialchars(strip_tags(trim($nameAndUrlPair->name)));
                            $url = htmlspecialchars(strip_tags(trim($nameAndUrlPair->url)));

                            // fill content with sanitized values
                            $content .= empty($url)
                                ? "[*][fa-*]{$name}[/*]"
                                : "[*][fa-url={$url}]{$name}[/url][/*]";
                        }
                        $content .= "[/list]";
                        $tutorialParts[$i]->content = $content;
                        $tutorialParts[$i]->isCollapsed = false;
                    } else {
                        // bind and sanitize incoming data
                        $title = $tutorialParts[$i]->title;
                        $content = $tutorialParts[$i]->content;
                        $isCollapsed = filter_var($tutorialParts[$i]->isCollapsed, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                        // replace values by the same values but sanitized
                        $tutorialParts[$i]->title =  $title;
                        $tutorialParts[$i]->content =  $content;
                        $tutorialParts[$i]->isCollapsed =  $isCollapsed;
                    }
                }

                // unset bound data
                unset($_POST['tutorialParts']);
                unset($_POST['chapters']);
                unset($_POST['linkedTuto']);

                $incomingTutorial = $this->bindIncomingTutorialData($_POST);

                // check for errors and return them if any
                $tutorialErrors = $this->validateIncomingTutorialData($incomingTutorial);
                if (!empty($tutorialErrors)) return array('errors' => $tutorialErrors);

                $tutorial = Course::jsonDeserialize($incomingTutorial);

                $tutorial->setTitle($tutorial->getTitle());
                $tutorial->setDescription($tutorial->getDescription());

                //no error, get the matching tutorial from database
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
                    $activity->setIsCollapsed($tutorialParts[$index]->isCollapsed);
                    try {
                        $this->entityManager->persist($courseLinkActivity);
                    } 
                    catch (\Error $e) {
                        echo $e->getMessage();
                    }

                    $this->entityManager->flush();
                }

                $this->saveLessonsIfNeeded($databaseCourse, $chapters);
                $this->saveRelatedTutorialsIfNeeded($databaseCourse, $linked);

                return $databaseCourse;
            },
            'delete' => function ($data) {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                $userId = intval($_SESSION['id']);
                $courseIdToDelete = !empty($_POST['id']) ? intval($_POST['id']) : null;
                $errors = [];

                // invalid course id, return an error
                if (empty($courseIdToDelete)) {
                    array_push($errors, array('errorType' => 'courseIdInvalid'));
                    return $errors;
                }

                $databaseCourse = $this->entityManager->getRepository('Learn\Entity\Course')->find($courseIdToDelete);

                // course does not exists, return an error
                if (!$databaseCourse) {
                    array_push($errors, array('errorType' => 'courseNotFound'));
                    return $errors;
                }

                // course exists but does not belong to current user, return an error
                if ($databaseCourse->getUser()->getId() != $userId) {
                    array_push($errors, array('errorType' => 'userNotCourseOwner'));
                    return $errors;
                }

                // remove entries from "learn_chapters_link_tutorials" table
                $lessonsDatabase = $this->entityManager->getRepository('Learn\Entity\Lesson')->findBy(array("tutorial" => $databaseCourse));
                foreach ($lessonsDatabase as $val) {
                    $this->entityManager->remove($val);
                }

                // remove entries from "learn_favorites" table
                $favoriteDatabase = $this->entityManager->getRepository('Learn\Entity\Favorite')->findBy(array("tutorial" => $databaseCourse));
                foreach ($favoriteDatabase as $val) {
                    $this->entityManager->remove($val);
                }

                // remove entries from "learn_tutorials_link_tutorials" table
                $linkedCoursesAsMain = $this->entityManager->getRepository('Learn\Entity\CourseLinkCourse')->findBy(array('tutorial1' => $databaseCourse));
                foreach ($linkedCoursesAsMain as $linkedCourseAsMain) {
                    $this->entityManager->remove($linkedCourseAsMain);
                    $this->entityManager->flush();
                }
                $linkedCoursesAsRelated = $this->entityManager->getRepository('Learn\Entity\CourseLinkCourse')->findBy(array('tutorial2' => $databaseCourse));
                foreach ($linkedCoursesAsRelated as $linkedCourseAsRelated) {
                    $this->entityManager->remove($linkedCourseAsRelated);
                    $this->entityManager->flush();
                }

                // flush anything that as not been saved in db
                $this->entityManager->flush();

                // remove the course and save the changes in db
                $this->entityManager->remove($databaseCourse);
                $this->entityManager->flush();
            },
            'get_all_user_resources' => function ($data) {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind data 
                $loggedUserId = !empty($_SESSION['id']) ? intval($_SESSION['id']) : null;
                $receivedUserId = !empty($_POST['user']) ? intval($_POST['user']) : null;
                /**
                 * $receivedUserId comes from a link => <a href="/userDetails?id=77">Equipe Vittascience</a> on learn page
                 * $loggedUserId comes from a request on profile page
                 */
                $userId = $receivedUserId ?? $loggedUserId ??   null;

                // invalid user id, return an error
                if (empty($userId)) {
                    return array('errors' => array('errorType' => 'userIdInvalid'));
                }

                $user = $this->entityManager
                    ->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $userId));

                // user not found, return an error
                if (!$user) {
                    return array('errors' => array('errorType' => 'userNotFound'));
                }

                if ($receivedUserId) {
                    // return data if a link has been clicked
                    return $this->entityManager
                        ->getRepository('Learn\Entity\Course')
                        ->findBy(array("user" => $user, "deleted" => false, "rights" => [1, 2]));
                }

                // return data if a user open its profile page
                return $this->entityManager->getRepository('Learn\Entity\Course')
                    ->findBy(array("user" => $user, "deleted" => false));
            },
            'get_courses_sorted_by' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // parse and sanitize incoming data
                list($incomingDoctrineProperty, $incomingOrderByValue) = explode('-', $_POST['resources-sorted-by']);
                $doctrineProperty = !empty($incomingDoctrineProperty)
                    ? htmlspecialchars(strip_tags(trim($incomingDoctrineProperty)))
                    : '';
                $orderByValue = !empty($incomingOrderByValue)
                    ? htmlspecialchars(strip_tags(trim(strtoupper($incomingOrderByValue))))
                    : '';

                // create empty errors array and check for errors
                $errors = [];
                if (empty($doctrineProperty)) array_push($errors, array('errorType' => 'doctrinePropertyInvalid'));
                if (empty($orderByValue)) array_push($errors, array('errorType' => 'orderByValueInvalid'));

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, get data from db
                $resources = $this->entityManager
                    ->getRepository(Course::class)
                    ->getCoursesSortedBy($doctrineProperty, $orderByValue);

                // create empty array to fill and return data
                $resourcesSortedBy = [];
                foreach ($resources as $resource) {
                    array_push($resourcesSortedBy, $resource->jsonSerialize());
                }
                return $resourcesSortedBy;
            },
            'upload_img_from_text_editor' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "uploadImgFromTextEditorNotAuthenticated"];

                // bind and sanitize incoming data and 
                $incomingData = $_FILES['image'];
                $imageError = intval($incomingData['error']);
                $imageName = !empty($incomingData['name']) ? htmlspecialchars(strip_tags(trim($incomingData['name']))) : "";
                $imageTempName = !empty($incomingData['tmp_name']) ? htmlspecialchars(strip_tags(trim($incomingData['tmp_name']))) : "";
                $extension = !empty($incomingData['type'])
                    ? htmlspecialchars(strip_tags(trim(
                        explode('/', $incomingData['type'])[1]
                    )))
                    : "";
                $imageSize = !empty($incomingData['size']) ? intval($incomingData['size']) : 0;

                // initialize $errors array and check for errors if any
                $errors = [];
                if ($imageError !== 0) array_push($errors, array("errorType" => "imageUploadError"));
                if (empty($imageName)) array_push($errors, array("errorType" => "invalidImageName"));
                if (empty($imageTempName)) array_push($errors, array("errorType" => "invalidImageTempName"));
                if (empty($extension)) array_push($errors, array("errorType" => "invalidImageExtension"));
                if (!in_array($extension, array("jpg", "jpeg", "png", "svg", "webp", "gif", "apng"))) {
                    array_push($errors, array("errorType" => "invalidImageExtension"));
                }
                if (empty($imageSize)) array_push($errors, array("errorType" => "invalidImageSize"));
                elseif ($imageSize > 1000000) array_push($errors, array("errorType" => "imageSizeToLarge"));

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, we can process the data
                // replace whitespaces by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
                $filenameWithoutSpaces = explode('.', str_replace(' ', '_', $imageName))[0];
                $filenameToUpload = time() . "_$filenameWithoutSpaces.$extension";

                // no errors, we can process the data
                $uploadDir = __DIR__ . "/../../../../public/content/user_data/resources";

                $success = move_uploaded_file($imageTempName, "$uploadDir/$filenameToUpload");

                // something went wrong while storing the image, return an error
                if (!$success) {
                    array_push($errors, array('errorType' => "imageNotStored"));
                    return array('errors' => $errors);
                }

                // no errors, return data
                return array(
                    "filename" => $filenameToUpload,
                    "src" => "/public/content/user_data/resources/$filenameToUpload"
                );
            },
            'upload_file_from_text_editor' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "uploadFileFromTextEditorNotAuthenticated"];
                // bind and sanitize incoming data and 
                $incomingData = $_FILES['file'];
                $fileError = intval($incomingData['error']);
                $fileName = !empty($incomingData['name']) ? htmlspecialchars(strip_tags(trim($incomingData['name']))) : "";
                $fileTempName = !empty($incomingData['tmp_name']) ? htmlspecialchars(strip_tags(trim($incomingData['tmp_name']))) : "";
                $extension = !empty($incomingData['type'])
                    ? htmlspecialchars(strip_tags(trim(
                        explode('/', $incomingData['type'])[1]
                    )))
                    : "";
                $fileSize = !empty($incomingData['size']) ? intval($incomingData['size']) : 0;

                // initialize $errors array and check for errors if any
                $errors = [];
                if ($fileError !== 0) array_push($errors, array("errorType" => "fileUploadError"));
                if (empty($fileName)) array_push($errors, array("errorType" => "invalidFileName"));
                if (empty($fileTempName)) array_push($errors, array("errorType" => "invalidFileTempName"));
                if (empty($extension)) array_push($errors, array("errorType" => "invalidFileExtension"));
                if (!in_array($extension, array("pdf"))) {
                    array_push($errors, array("errorType" => "invalidFileExtension"));
                }
                if (empty($fileSize)) array_push($errors, array("errorType" => "invalidFileSize"));
                elseif ($fileSize > 5000000) array_push($errors, array("errorType" => "fileSizeToLarge"));

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, we can process the data
                // replace whitespaces by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
                $filenameWithoutSpaces = explode('.', str_replace(' ', '_', $fileName))[0];
                $filenameToUpload = time() . "_$filenameWithoutSpaces.$extension";

                // set the target dir and move file
                $uploadDir = __DIR__ . "/../../../../public/content/user_data/resources";
                $success = move_uploaded_file($fileTempName, "$uploadDir/$filenameToUpload");

                // something went wrong while storing the file, return an error
                if (!$success) {
                    array_push($errors, array('errorType' => "fileNotStored"));
                    return array('errors' => $errors);
                }

                // no errors, return data
                return array(
                    "filename" => $filenameToUpload,
                    "src" => "/public/content/user_data/resources/$filenameToUpload"
                );
            },
            'add_from_classroom' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["error" => "Not Authenticated"];

                try {
                    $courseData = json_decode($_POST['course'], true);
                    // sanitize incoming data
                    $activities = $courseData['courses'] ?? "";
                    $title = $courseData['title'] ? htmlspecialchars(strip_tags(trim($courseData['title']))) : "";
                    $description = $courseData['description'] ? htmlspecialchars(strip_tags(trim($courseData['description']))) : "";
                    $duration = intval($courseData['parameters']['duration']);
                    $difficulty = intval($courseData['parameters']['difficulty']);
                    $language = intval($courseData['parameters']['language']);
                    $license = intval($courseData['parameters']['license']);
                    $format = boolval($courseData['parameters']['format']);
                    $optionalData = !empty($courseData['parameters']['optionalData']) ? json_encode($courseData['parameters']['optionalData']) : null;
                    $folderId = !empty($_POST['folder']) ? htmlspecialchars($_POST['folder']) : null;

                    // initialize $errors array and check for errors if any
                    $errors = [];
                    if (empty($activities)) array_push($errors, array("errorType" => "invalidActivities"));
                    if (empty($title)) array_push($errors, array("errorType" => "invalidTitle"));
                    if (empty($description)) array_push($errors, array("errorType" => "invalidDescription"));
                    if (empty($duration)) array_push($errors, array("errorType" => "invalidDuration"));

                    // some errors found, return them
                    if (!empty($errors)) return array('errors' => $errors);
                    // no errors, we can process the data

                    $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
                    $course = new Course();
                    $course->setTitle($title);
                    $course->setDescription($description);
                    // not nullable but we don't ask it to the user for now @Rémi 20221114
                    $course->setSupport(0);
                    $course->setFork(null);
                    $course->setDuration($duration);
                    $course->setDifficulty($difficulty);
                    $course->setLang($language);
                    $course->setUser($user);
                    $course->setRights($license);
                    $course->setDeleted(false);
                    $course->setFormat($format);
                    $course->setOptionalData($optionalData);

                    if ($folderId != null) {
                        $folder = $this->entityManager->getRepository(Folders::class)->find($folderId);
                        if ($folder) {
                            $course->setFolder($folder);
                        }
                    }
                    $this->entityManager->persist($course);
                    $this->entityManager->flush();

                    foreach ($activities as $index => $activity) {
                        $acti = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $activity['id']]);
                        $courseLinkActivity = new CourseLinkActivity($course, $acti, $index);
                        $this->entityManager->persist($courseLinkActivity);
                    }

                    $this->entityManager->flush();
                    return ["success" => true, "message" => "course added successfully", "course" => $course];
                } catch (\Error $error) {
                    return ["success" => false, "message" => $error->getMessage()];
                }
            },
            'upload_img_from_classroom' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "NotAuthenticated"];

                // bind and sanitize incoming data and 
                $incomingData = $_FILES['image'];
                $imageError = intval($incomingData['error']);
                $imageName = !empty($incomingData['name']) ? htmlspecialchars(strip_tags(trim($incomingData['name']))) : "";
                $imageTempName = !empty($incomingData['tmp_name']) ? htmlspecialchars(strip_tags(trim($incomingData['tmp_name']))) : "";
                $extension = !empty($incomingData['type']) ? htmlspecialchars(strip_tags(trim(explode('/', $incomingData['type'])[1]))) : "";
                $imageSize = !empty($incomingData['size']) ? intval($incomingData['size']) : 0;

                // initialize $errors array and check for errors if any
                $errors = [];
                if ($imageError !== 0) array_push($errors, array("errorType" => "imageUploadError"));
                if (empty($imageName)) array_push($errors, array("errorType" => "invalidImageName"));
                if (empty($imageTempName)) array_push($errors, array("errorType" => "invalidImageTempName"));
                if (empty($extension)) array_push($errors, array("errorType" => "invalidImageExtension"));
                if (!in_array($extension, array("jpg", "jpeg", "png", "svg", "webp", "gif", "apng"))) {
                    array_push($errors, array("errorType" => "invalidImageExtension"));
                }
                if (empty($imageSize)) array_push($errors, array("errorType" => "invalidImageSize"));
                elseif ($imageSize > 1000000) array_push($errors, array("errorType" => "imageSizeToLarge"));

                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, we can process the data
                // replace whitespaces by _ and get the first chunk in case of duplicated ".someMisleadingExtensionBeforeTheRealFileExtension"
                $filenameWithoutSpaces = explode('.', str_replace(' ', '_', $imageName))[0];
                $filenameToUpload = time() . "_$filenameWithoutSpaces.$extension";

                // no errors, we can process the data
                $uploadDir = __DIR__ . "/../../../../classroom/assets/media/uploads/";
                //$uploadDir = __DIR__ . "/../../../../public/content/user_data/resources";

                $success = move_uploaded_file($imageTempName, "$uploadDir/$filenameToUpload");

                // something went wrong while storing the image, return an error
                if (!$success) {
                    array_push($errors, array('errorType' => "imageNotStored"));
                    return array('errors' => $errors);
                }

                // no errors, return data
                return array(
                    "filename" => $filenameToUpload,
                    "src" => "/classroom/assets/media/uploads/$filenameToUpload"
                );
            },
            'update_from_classroom' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["error" => "Not Authenticated"];

                try {
                    $courseData = json_decode($_POST['course'], true);

                    // sanitize incoming data
                    $activities = $courseData['courses'] ?? "";
                    $courseId = htmlspecialchars(strip_tags(trim($_POST['courseId']))) ?? "";
                    $title = $courseData['title'] ? htmlspecialchars(strip_tags(trim($courseData['title']))) : "";
                    $description = $courseData['description'] ? htmlspecialchars(strip_tags(trim($courseData['description']))) : "";
                    $duration = intval($courseData['parameters']['duration']);
                    $difficulty = intval($courseData['parameters']['difficulty']);
                    $language = intval($courseData['parameters']['language']);
                    $license = intval($courseData['parameters']['license']);
                    $format = boolval($courseData['parameters']['format']);
                    $optionalData = json_encode($courseData['parameters']['optionalData']);

                    $dateBegin = !empty($_POST['dateBegin']) ? $_POST['dateBegin'] : '';
                    $dateEnd = !empty($_POST['dateEnd']) ? $_POST['dateEnd'] : '';

                    $dateTimeBegin = new \DateTime($dateBegin);
                    $dayeTimeEnd = new \DateTime($dateEnd);

                    $lang = [0 => "Français", 1 => "Anglais", 2 => "Italien", 3 => "Arabe"];

                    // initialize $errors array and check for errors if any
                    $errors = [];
                    if (empty($activities)) array_push($errors, array("errorType" => "invalidActivities"));
                    if (empty($title)) array_push($errors, array("errorType" => "invalidTitle"));
                    if (empty($description)) array_push($errors, array("errorType" => "invalidDescription"));
                    if (empty($duration)) array_push($errors, array("errorType" => "invalidDuration"));

                    // some errors found, return them
                    if (!empty($errors)) return array('errors' => $errors);
                    // no errors, we can process the data

                    $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
                    $course = $this->entityManager->getRepository(Course::class)->findOneBy(["id" => $courseId]);


                    $course->setTitle($title);
                    $course->setDescription($description);
                    $course->setFork(null);
                    $course->setDuration($duration);
                    $course->setDifficulty($difficulty);
                    $course->setLang($lang[$language]);
                    $course->setUser($user);
                    $course->setRights($license);
                    $course->setDeleted(false);
                    $course->setFormat($format);
                    $course->setOptionalData($optionalData);
                    $this->entityManager->persist($course);
                    $this->entityManager->flush();

                    // get all courselinkactivity
                    $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);
                    // delete all courselinkactivity
                    foreach ($courseLinkActivities as $courseLinkActivity) {
                        $this->entityManager->remove($courseLinkActivity);
                    }
                    $this->entityManager->flush();

                    foreach ($activities as $index => $activity) {
                        $acti = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $activity['id']]);
                        if ($acti) {
                            $courseLinkActivity = new CourseLinkActivity($course, $acti, $index);
                            $this->entityManager->persist($courseLinkActivity);
                        }
                    }
                    $this->entityManager->flush();

                    $this->manageAttriForActivitiesAddedToCourse($course, $activities, $dateTimeBegin, $dayeTimeEnd);

                    return ["success" => true, "message" => "course updated successfully", "course" => $course];
                } catch (\Error $error) {
                    return ["success" => false, "message" => $error->getMessage()];
                }
            },
            'delete_from_classroom' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["error" => "Not Authenticated"];

                try {

                    $courseId = htmlspecialchars(strip_tags(trim($_POST['courseId']))) ?? "";
                    if (empty($courseId)) return ["error" => "Invalid course id"];
                    // no errors, we can process the data

                    $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
                    if (!$user) return ["error" => "Not authorized"];

                    $course = $this->entityManager->getRepository(Course::class)->findOneBy(["id" => $courseId, "user" => $user]);
                    if (!$course) return ["error" => "Not authorized"];
                    
                    // get forks of the course to delete the reference
                    $forks = $this->entityManager->getRepository(Course::class)->findBy(["fork" => $course]);
                    foreach ($forks as $fork) {
                        $fork->setFork(null);
                        $this->entityManager->persist($fork);
                    }

                    $this->entityManager->remove($course);

                    $courseLinkActivities = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(["course" => $course]);
                    foreach ($courseLinkActivities as $courseLinkActivity) {
                        $this->entityManager->remove($courseLinkActivity);
                    }

                    $courseLinkActivity = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);
                    foreach ($courseLinkActivity as $cla) {
                        // get userlinkactivity 
                        $userLinkActivity = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(["activity" => $cla->getActivity(), "isFromCourse" => 1, "course" => $course->getId()]);
                        foreach ($userLinkActivity as $ula) {
                            $this->entityManager->remove($ula);
                        }
                        $this->entityManager->remove($cla);
                    }

                    $this->entityManager->flush();
                    return ["success" => true, "message" => "Course and course link successfully deleted"];
                } catch (\Error $error) {
                    return ["success" => false, "message" => $error->getMessage()];
                }
            },
            'moveCourseToFolder'  => function ($data) {

                $courseId = htmlspecialchars($data['courseId']);
                $folderId = htmlspecialchars($data['folderId']);

                $course = $this->entityManager->getRepository(Course::class)->find(["id" => $courseId]);

                // check if allowed 
                $requester_id = $_SESSION['id'];
                $creator_id = $course->getUser();
                $Allowed = $this->isAllowed($creator_id->getId(), $requester_id);

                if (!$Allowed) {
                    return array(
                        'error' => 'notAllowed'
                    );
                }

                $folder = $this->entityManager->getRepository(Folders::class)->find($folderId);
                $course->setFolder($folder);
                $this->entityManager->persist($course);
                $this->entityManager->flush();

                return [
                    'success' => true,
                    'course' => $course,
                    'folder' => $folder
                ];
            },
            'get_one_from_classroom'  => function ($data) {

                $courseId = htmlspecialchars($data['courseId']);
                if (empty($courseId)) return ["error" => "Invalid course id"];

                $course = $this->entityManager->getRepository(Course::class)->find(["id" => $courseId]);

                if (!$course) return ["error" => "course not found"];
                if ($course->getUser()->getId() != $_SESSION['id']) return ["error" => "Not authorized"];

                // get course link activities
                $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);

                // order activities by position
                usort($courseLinkActivities, function ($a, $b) {
                    return $a->getIndexOrder() <=> $b->getIndexOrder();
                });

                $activities = [];
                foreach ($courseLinkActivities as $cla) {
                    $activities[] = $cla->getActivity();
                }
                $courseSerialized = $course->jsonSerialize();

                $courseSerialized['activities'] = $activities;

                return ["success" => true, "course" => $courseSerialized];
            },
            'duplicate_from_classroom'  => function ($data) {

                $courseId = htmlspecialchars($data['courseId']);
                if (empty($courseId)) return ["error" => "Invalid course id"];

                $course = $this->entityManager->getRepository(Course::class)->find(["id" => $courseId]);

                if (!$course) return ["error" => "course not found"];
                if ($course->getUser()->getId() != $_SESSION['id']) return ["error" => "Not authorized"];

                // get course link activities
                $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);

                $title = "";
                $newTitle = "";
                $regexTrigger = "";

                if ($course->getTitle()) {
                    $title = $course->getTitle();
                    $regexTrigger = preg_match('/\(.*?\)/', $course->getTitle(), $title);
                    if ($regexTrigger) {
                        $result = str_replace('(', '', $title);
                        $number = str_replace(')', '', $result);
                        $increment = (intval($number[0]) + 1);
                        $newTitle = str_replace($title, "(" . $increment . ")", $course->getTitle());
                    } else {
                        $title = 1;
                        $newTitle = $course->getTitle() . " (1)";
                    }
                }

                $courseDuplicate = new Course();
                $courseDuplicate->setTitle($newTitle);
                $courseDuplicate->setDescription($course->getDescription());
                $courseDuplicate->setDuration($course->getDuration());
                $courseDuplicate->setDifficulty($course->getDifficulty());
                $courseDuplicate->setLang($course->getLang());
                $courseDuplicate->setUser($course->getUser());
                $courseDuplicate->setRights($course->getRights());
                $courseDuplicate->setDeleted(false);
                $courseDuplicate->setFolder($course->getFolder());
                $courseDuplicate->setFormat($course->getFormat());
                $this->entityManager->persist($courseDuplicate);
                $this->entityManager->flush();

                foreach ($courseLinkActivities as $key => $cla) {
                    $courseLinkActivity = new CourseLinkActivity($courseDuplicate, $cla->getActivity(), $key);
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
            },
            'set_state_from_course' => function ($data) {
                $courseId = htmlspecialchars($data['courseId']);
                $state = htmlspecialchars($data['state']);
                $courseLinkUserId = htmlspecialchars($data['courseLinkUserId']);
                $userId = htmlspecialchars($_SESSION['id']);

                try {
                    $course = $this->entityManager->getRepository(Course::class)->find(["id" => $courseId]);
                    $courseLinkUser = $this->entityManager->getRepository(CourseLinkUser::class)->findOneBy(["course" => $course, "user" => $userId, "id" => $courseLinkUserId]);
                    $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(["course" => $course]);
                    if ($courseLinkActivities) {
                        if (count($courseLinkActivities) == $state) {
                            $courseLinkUser->setCourseState(999);
                        } else {
                            $courseLinkUser->setCourseState($state);
                        }
                    }
                    $this->entityManager->persist($courseLinkUser);
                    $this->entityManager->flush();
                    return ["success" => true, "courseLinkUser" => $courseLinkUser];
                } catch (\Error $error) {
                    return ["success" => false, "message" => $error->getMessage()];
                }
            },
            'debug_course' => function () {
                try {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
                    if (!$user) return ["error" => "Not authorized"];
                    $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(["user" => $user]);
                    if (!$userR->getIsAdmin()) return ["error" => "Not authorized"];
                    
                    $this->updateCourseToNewSystem();
                    return ["success" => true];
                } catch (\Error $error) {
                    return ["success" => false, "message" => $error->getMessage()];
                }
            }
        );
    }

    private function bindIncomingTutorialData($incomingData)
    {
        $tutorial = new \stdClass;
        if (array_key_exists('id', $incomingData) && !empty($incomingData['id'])) {
            $tutorial->id = intval($incomingData['id']);
        }
        $tutorial->title = !empty($incomingData['title']) ? $incomingData['title'] : '';
        $tutorial->description = !empty($incomingData['description']) ? $incomingData['description'] : '';
        $tutorial->difficulty = !empty($incomingData['difficulty']) ? intval($incomingData['difficulty']) : 0;
        $tutorial->duration = !empty($incomingData['duration']) ? intval($incomingData['duration']) : 3600;
        $tutorial->lang = !empty($incomingData['lang']) ? htmlspecialchars(strip_tags(trim($incomingData['lang']))) : '';
        $tutorial->support = !empty($incomingData['support']) ? intval($incomingData['support']) : 0;
        $tutorial->rights = !empty($incomingData['rights']) ? intval($incomingData['rights']) : 0;
        $tutorial->views = !empty($incomingData['views']) ? intval($incomingData['views']) : 0;

        return $tutorial;
    }

    private function validateIncomingTutorialData($tutorial)
    {
        $errors = [];

        // check for errors
        if (empty($tutorial->title)) array_push($errors, array('type' => 'titleInvalid'));
        if (empty($tutorial->description)) array_push($errors, array('type' => 'descriptionInvalid'));
        if (!is_numeric($tutorial->difficulty)) array_push($errors, array('type' => 'difficultyInvalid'));
        elseif ($tutorial->difficulty < 0 || $tutorial->difficulty > 3) array_push($errors, array('type' => 'difficultyInvalid'));
        if (empty($tutorial->duration)) array_push($errors, array('type' => 'durationInvalid'));
        if (empty($tutorial->lang)) array_push($errors, array('type' => 'langInvalid'));
        if (!is_numeric($tutorial->support)) array_push($errors, array('type' => 'supportInvalid'));
        elseif ($tutorial->support < 0) array_push($errors, array('type' => 'supportInvalid'));
        if ($tutorial->rights < 0 || $tutorial->rights > 3) array_push($errors, array('type' => 'rightsInvalid'));

        return $errors;
    }

    private function sanitizeAndFormatFilterParams($incomingFilters)
    {
        $sanitizedFilters = [];
        if (!empty($incomingFilters["support"])) {
            $supports = [];
            foreach ($incomingFilters["support"] as $incomingSupport) {
                array_push($supports, intval($incomingSupport));
            }
            $sanitizedFilters['support'] = "(" . implode(",", $supports) . ")";
        }
        if (!empty($incomingFilters["difficulty"])) {
            $difficulties = [];
            foreach ($incomingFilters["difficulty"] as $incomingDifficulty) {
                array_push($difficulties, intval($incomingDifficulty));
            }
            $sanitizedFilters['difficulty'] = "(" . implode(",", $difficulties) . ")";
        }
        if (!empty($incomingFilters["lang"])) {
            $languages = [];
            foreach ($incomingFilters["lang"] as $incomingLang) {
                array_push($languages, "'" . htmlspecialchars(strip_tags(trim($incomingLang))) . "'");
            }
            $sanitizedFilters['lang'] = "(" . implode(",", $languages) . ")";
        }

        return $sanitizedFilters;
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

    private function saveRelatedTutorialsIfNeeded($mainTutorial, $relatedTutorialsIds)
    {
        foreach ($relatedTutorialsIds as $relatedTutorialId) {
            $tutorial2Exists = $this->entityManager->getRepository('Learn\Entity\Course')->find($relatedTutorialId);

            if ($tutorial2Exists) {
                $linkedTutoRelationExists = $this->entityManager
                    ->getRepository('Learn\Entity\CourseLinkCourse')
                    ->findOneBy(array(
                        'tutorial1' => $mainTutorial->getId(),
                        'tutorial2' => $tutorial2Exists->getId()
                    ));

                if (!$linkedTutoRelationExists) {
                    $related = new CourseLinkCourse($mainTutorial, $tutorial2Exists);
                    $this->entityManager->persist($related);
                    $this->entityManager->flush();
                }
            }
        }
    }

    private function saveLessonsIfNeeded($mainTutorial, $chapterIds)
    {
        //add lessons to the tutorial
        foreach ($chapterIds as $chapterId) {
            $chapterFound = $this->entityManager->getRepository('Learn\Entity\Chapter')->find($chapterId);

            if ($chapterFound) {
                $lessonExists = $this->entityManager->getRepository(Lesson::class)
                    ->findOneBy(array(
                        'chapter' => $chapterFound,
                        'tutorial' => $mainTutorial
                    ));

                if (!$lessonExists) {
                    $lesson = new Lesson();
                    $lesson->setCourse($mainTutorial);
                    $lesson->setChapter($chapterFound);
                    $this->entityManager->persist($lesson);
                    $this->entityManager->flush();
                }
            }
        }
    }


    private function manageAttriForActivitiesAddedToCourse($course, $activities, $dateTimeBegin, $dayeTimeEnd) {
        //Manage attribution for the activites added to the course
        $randomStr = "";
        // cgecj if the course is linked to user
        $linkCourseToClassroomExists = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['course' => $course->getId()]);
        // if the course is linked to user
        if ($linkCourseToClassroomExists) {
            // foreach user linked to the course
            foreach ($linkCourseToClassroomExists as $link) {
                // get the user
                $userLinked = $link->getUser();
                // for each activity in the course
                foreach ($activities as $key => $activity) {

                    $randomStr = strval(time()) . $key;
                    //check if activityLinkUser exists
                    $activityLinkUserExists = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(['activity' => $activity['id'], 'user' => $userLinked->getId(), 'isFromCourse' => 1, 'course' => $course->getId()]);
                    if (!$activityLinkUserExists) {
                        $acti = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $activity['id']]);

                        if ($acti) {

                            $classroomLinkUser = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy(['user' => $link->getUser()->getId()]);
                            if ($classroomLinkUser) {
                                $classroomId = $classroomLinkUser->getClassroom()->getId();
                                // get students in classroom
                                $students = $this->entityManager->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomId, 'rights' => 0]);
                                if (count($students) > 0) {
                                    foreach ($students as $student) {
                                        if ($student->getUser()->getId() != $link->getUser()->getId()) {
                                            $actlinkuser = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(['course' => $course, 'activity' => $activity['id'], 'user' => $student->getUser()->getId()]);
                                            if ($actlinkuser) {
                                                $randomStr = $actlinkuser->getReference();
                                                $dateTimeBegin = $actlinkuser->getDateBegin();
                                                $dayeTimeEnd = $actlinkuser->getDateEnd();
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            $activityLinkUser = new ActivityLinkUser($acti, $userLinked);
                            if ($acti->getType() == "reading" && $course->getFormat() == 1) {
                                $activityLinkUser->setCorrection(2);
                                $activityLinkUser->setNote(4);
                                if ($course->getFormat() == 1) {
                                    $link->setCourseState($link->getCourseState() + 1);
                                }
                            }
                            $activityLinkUser->setCourse($course);
                            $activityLinkUser->setReference($randomStr);
                            $activityLinkUser->setDateBegin($dateTimeBegin);
                            $activityLinkUser->setDateEnd($dayeTimeEnd);
                            $activityLinkUser->setIsFromCourse(1);
                            $this->entityManager->persist($activityLinkUser);
                        }
                    }
                }
            }
            $this->entityManager->flush();
        }
    }

    private function updateCourseToNewSystem() {
        // Get all courses
        $myCourses = $this->entityManager->getRepository(Course::class)->findAll();
        $myCoursesLinkUserId = [];

        // loop in all course
        foreach ($myCourses as $course) { 
            // get all courselinkuser with reference null
            $oldCourses = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['reference' => null, 'course' => $course->getId()]);
            // if there is some
            if ($oldCourses) {
                foreach ($oldCourses as $oldCourse) {
                    $myCoursesLinkUserId[] = [$oldCourse->getId(), $course->getId()];
                }
            }
        }


        $activitiesLinkUser = [];
        $activitiesReferences = [];
        // for each course link user
        foreach ($myCoursesLinkUserId as $courseId) {
            $activitiesLinkCourse = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $courseId[1]]);
            foreach ($activitiesLinkCourse as $activityLinkCourse) {
                $activities = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(['activity' => $activityLinkCourse->getActivity()->getId(), 'course' => $courseId, 'isFromCourse' => 1]);
                foreach ($activities as $activity) {
                    $activitiesLinkUser[] = $activity->getId();
                    if (!in_array([$activity->getReference(), $courseId[1], $courseId[0]], $activitiesReferences)) {
                        $activitiesReferences[] = [$activity->getReference(), $courseId[1], $courseId[0]];
                    }
                }
            }
        }

        $coursesActivityReferences = [];
        foreach ($activitiesReferences as $reference) {
            if (!in_array($reference, $coursesActivityReferences)) {
                if (!array_key_exists($reference[1], $coursesActivityReferences)) {
                    $coursesActivityReferences[$reference[1]] = [];
                }

                if (!in_array($reference[0], $coursesActivityReferences[$reference[1]])) {
                    $coursesActivityReferences[$reference[1]][] = $reference[0];
                }
            }
        }

        foreach ($myCoursesLinkUserId as $courseId) {
            $oldCourses = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['reference' => null, 'course' => $courseId[1]]);
            foreach ($oldCourses as $oldCourse) {
                $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $courseId[1]]);
                // we need the exact same number of activities
                if (count($courseLinkActivities) == count($coursesActivityReferences[$courseId[1]])) {
                    $oldCourse->setActivitiesReferences(json_encode(array_values(($coursesActivityReferences[$courseId[1]]))));
                } else {
                    $references = [];
                    for($i = 0; $i < count($courseLinkActivities); $i++) {
                        $references[] = $coursesActivityReferences[$courseId[1]][$i];
                    }
                    $oldCourse->setActivitiesReferences(json_encode(array_values($references)));
                }
                $ref = substr($coursesActivityReferences[$courseId[1]][0], 0, -1);
                $oldCourse->setReference($ref);
                $this->entityManager->persist($oldCourse);
                $this->entityManager->flush();
            }
        }
    }

    private function sendToMailerlite($rights, $id, $email) {
        switch ($rights) {
            case 1:
                $res = "Publique CC-BY-NC-SA";
                break;
            case 2:
                $res = "Publique CC-BY-NC-ND";
                break;
            case 3:
                $res = "Déréférencée";
                break;
            default:
                $res = "Privée";
                break;
        }
        $groupsApi = (new \MailerLiteApi\MailerLite($_ENV['VS_SHOP_MAILERLITE_API_KEY']))->groups();
        $subscriber = [
            'email' => $email,
            'fields' => [
                'ressources_license' => $res,
                'ressources_link' => "https://vittascience.com/learn/tutorial.php?id=".$id
            ],
        ];
        $response = $groupsApi->addSubscriber("111807620", $subscriber);
    }
}
