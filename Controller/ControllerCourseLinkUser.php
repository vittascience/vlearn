<?php

namespace Learn\Controller;

use Learn\Entity\Course;
use Learn\Entity\Activity;
use Learn\Entity\CourseLinkActivity;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;

class ControllerCourseLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_my_courses_as_teacher' => function () {
                $arrayCoursesResult = $this->entityManager->getRepository(Course::class)->findBy(['user' => $this->user]);
                $arrayResult = [];

                foreach ($arrayCoursesResult as $courses) {
                    $myCourse = $courses->jsonSerialize();
                    $courseLinkActivity = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $myCourse['id']]);
                    $myActivities = [];
                    foreach ($courseLinkActivity as $link) {
                        $activity = $this->entityManager->getRepository(Activity::class)->findOneBy(['id' => $link->getActivity()->getId()]);
                        $myActivities[] = $activity->jsonSerialize();
                    }
                    $myCourse['activities'] = $myActivities;
                    array_push($arrayResult, $myCourse);
                }

                return  $arrayResult;
            },

            'link_user_to_course' => function () {

                $courseId = htmlspecialchars($_POST['courseId']);
                $classroomId = $_POST['classrooms'];
                $students = $_POST['students'];


                foreach ($classroomId as $classId) {
                    $teacher = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy(['user' => $_SESSION['id'], 'classroom' => $classId, 'rights' => 2]);
                    if (!$teacher) {
                        return ["success" => false, "message" => "Vous n'êtes pas professeur de cette classe"];
                    }
                }

                // get the course 
                $course = $this->entityManager->getRepository(Course::class)->findOneBy(['id' => $courseId]);
                // get the activities of the course
                $courseLinkActivity = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $courseId]);

                // for each student
                foreach ($students as $studentId) {
                    foreach ($courseLinkActivity as $link) {

                        $activity = $this->entityManager->getRepository(Activity::class)->findOneBy(['id' => $link->getActivity()->getId()]);
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $studentId]);
                        $linkActivityToUser = new ActivityLinkUser($activity, $user, new \DateTime(), null);
                        $linkActivityToUser->is
                    }
                }




                return true;
            },
        );
    }
}
