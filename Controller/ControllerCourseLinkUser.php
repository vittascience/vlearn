<?php

namespace Learn\Controller;

use Learn\Entity\Course;

class ControllerCourseLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_my_courses_as_teacher' => function () {
                $arrayCoursesResult = $this->entityManager->getRepository(Course::class)->findBy(['user' => $this->user]);
                return  $arrayCoursesResult;
            },
        );
    }
}
