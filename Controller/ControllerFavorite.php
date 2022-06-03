<?php

namespace Learn\Controller;

use User\Entity\User;
use Learn\Entity\Favorite;

class ControllerFavorite extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_mine' => function () {
                if ($this->user) {
                    $favorites = $this->entityManager->getRepository('Learn\Entity\Favorite')
                        ->findBy(array("user" => $this->user['id']));
                    $arrayResult = array();
                    foreach ($favorites as $favorite) {
                        $result = ["user" => $favorite->getUser()->getId(), "tutorial" => $favorite->getTutorial()->getId()];
                        array_push($arrayResult, $result);
                    }
                    return  $arrayResult;
                } else {
                    return false;
                }
            },
            'update' => function ($data) {
                $user = $this->entityManager->getRepository(User::class)->find($_SESSION['id']);
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial']);
                $favorite = new Favorite($user, $tutorial);
                $this->entityManager->persist($favorite);
                $this->entityManager->flush();
                return true;
            },
            'delete' => function ($data) {
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->find($data['tutorial']);
                $favorite = $this->entityManager->getRepository("Learn\Entity\Favorite")
                    ->findOneBy(array("user" => $this->user, "tutorial" => $tutorial));
                $this->entityManager->remove($favorite);
                $this->entityManager->flush();
                return true;
            },
            'get_my_favorites_tutorials' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                // get logged user data from db and its favorite tutorials
                $user = $this->entityManager
                            ->getRepository(User::class)
                            ->find($_SESSION['id']);

                $favorites = $this->entityManager
                            ->getRepository('Learn\Entity\Favorite')
                            ->findBy(array('user' => $user));

                // create empty array to return and fill it
                $dataToReturn = [];
                foreach ($favorites as $favorite) {
                    array_push($dataToReturn, $favorite->getTutorial());
                }
                return $dataToReturn;
            }
        );
    }
}
