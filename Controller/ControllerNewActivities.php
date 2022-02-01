<?php

namespace Learn\Controller;

use Learn\Entity\Activity;
use Learn\Controller\Controller;

class ControllerNewActivities extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(            
            'get_all_apps' => function () {
                $Apps = $this->entityManager->getRepository(Applications::class)->findAll();
                return $Apps;
            },
            'create_exercice' => function ($data) {
                //$exercice = new Activity();
                return true;
            },
            'get_one_exercice' => function ($data) {
                return true;
            },
        );
    }
}