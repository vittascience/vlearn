<?php

namespace Learn\Controller;

class ControllerCollection extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_all' => function () {
                $arrayResult = $this->entityManager->getRepository('Learn\Entity\Collection')
                    ->findAll();
                return  $arrayResult;
            },
            'get_one' => function ($data) {
                return $this->entityManager->getRepository("Learn\Entity\Collection")
                    ->findBy(array("id" => $data['id']));
            }
        );
    }
}
