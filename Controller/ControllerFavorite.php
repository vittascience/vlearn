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
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                $userId = intval($_SESSION['id']);

                // get logged user data from db and its favorite tutorials
                $user = $this->entityManager
                            ->getRepository(User::class)
                            ->find($userId);
                
                // no user with this id 
                if(!$user) return false;
                
                // user found
                $favorites = $this->entityManager
                                    ->getRepository('Learn\Entity\Favorite')
                                    ->findBy(array("user" => $user));

                // create empty array to fill with data
                $arrayResult = array();
                foreach ($favorites as $favorite) {
                    $result = array(
                        "user" => $favorite->getUser()->getId(), 
                        "tutorial" => $favorite->getTutorial()->getId()
                    );
                    array_push($arrayResult, $result);
                }
                return  $arrayResult;
            },
            'update' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                // bind data
                $userId = intval($_SESSION['id']);
                $tutorialId = !empty($_POST['tutorial']) ? intval($_POST['tutorial']) : null;
               
                // create empty errors array and check for errors
                $errors = [];
                if(empty($tutorialId)){
                    array_push($errors, array('errorType'=> 'tutorialIdInvalid'));
                    return array('errors' => $errors);
                }
                
                // get logged user data from db and its favorite tutorials
                $user = $this->entityManager
                            ->getRepository(User::class)
                            ->find($userId);
                
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->find($tutorialId);
                $favorite = new Favorite($user, $tutorial);
                $this->entityManager->persist($favorite);
                $this->entityManager->flush();
                return true;
            },
            'delete' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "userNotAuthenticated"];

                // bind data
                $userId = intval($_SESSION['id']);
                $tutorialId = !empty($_POST['tutorial']) ? intval($_POST['tutorial']) : null;
               
                // create empty errors array and check for errors
                $errors = [];
                if(empty($tutorialId)){
                    array_push($errors, array('errorType'=> 'tutorialIdInvalid'));
                    return array('errors' => $errors);
                }
                
                // get logged user data from db and its favorite tutorials
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->find($tutorialId);
                $favorite = $this->entityManager->getRepository("Learn\Entity\Favorite")
                    ->findOneBy(array(
                        "user" => $user, 
                        "tutorial" => $tutorial
                    ));

                // favorite not found in db return an error
                if(!$favorite){
                    array_push($errors, array('errorType'=> 'favoriteTutorialNotFound'));
                    return array('errors' => $errors);
                }
                
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
