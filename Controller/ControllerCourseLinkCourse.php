<?php

namespace Learn\Controller;

use Learn\Entity\CourseLinkCourse;

class ControllerCourseLinkCourse extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_by_tutorial' => function ($data) {
                $arrayResult = [];
                $tuto = $this->entityManager->getRepository('Learn\Entity\CourseLinkCourse')
                    ->findBy(array("tutorial1" => $data['id']));
                foreach ($tuto as $t) {
                    $result = [
                        "id" => $t->getCourse2()->getId(),
                        "title" => $t->getCourse2()->getTitle(),
                        "picture" => $t->getCourse2()->getImg()
                    ];
                    array_push($arrayResult, $result);
                }
                return  $arrayResult;
            },
            'add' => function ($data) {
                if (!$this->canEditTutorial($data['id'] ?? 0)) return ['error' => 'notAllowed'];
                $tutorial1 = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['id']);
                foreach (json_decode($data['linkedTuto']) as $tuto) {
                    $tutorial2 = $this->entityManager->getRepository('Learn\Entity\Course')->find($tuto);
                    $related = new CourseLinkCourse($tutorial1, $tutorial2);
                    $this->entityManager->persist($related);
                }
                $this->entityManager->flush();
                return true;
            },
            'update' => function ($data) {
                if (!$this->canEditTutorial($data['tutorial1'] ?? 0)) return ['error' => 'notAllowed'];
                $tutorial1 = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial1']);
                $tutorial2 = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial2']);
                $related = new CourseLinkCourse($tutorial1, $tutorial2);
                $this->entityManager->persist($related);
                $this->entityManager->flush();
                return true;
            },
            'delete' => function ($data) {
                if (!$this->canEditTutorial($data['tutorial1'] ?? 0)) return ['error' => 'notAllowed'];
                $tutorial1 = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial1']);
                $tutorial2 = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial2']);
                $related = $this->entityManager->getRepository("Learn\Entity\CourseLinkCourse")
                    ->findOneBy(array("tutorial1" => $tutorial1, "tutorial2" => $tutorial2));
                $this->entityManager->remove($related);
                $this->entityManager->flush();
                return true;
            }
        );
    }

    // Only the tutorial's owner or a superadmin manages its related tutorials
    private function canEditTutorial($tutorialId): bool
    {
        if (empty($_SESSION['id'])) return false;
        $userId = intval($_SESSION['id']);
        $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->find(intval($tutorialId));
        if (!$tutorial) return false;
        if ($tutorial->getUser() && $tutorial->getUser()->getId() == $userId) return true;
        $regular = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(['user' => $userId]);
        return $regular && $regular->getIsAdmin();
    }
}
