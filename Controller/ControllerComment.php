<?php

namespace Learn\Controller;

use Learn\Entity\Comment;
use Utils\Mailer;

class ControllerComment extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'add' => function ($data) {
                try {
                    $commentAnswered = NULL;
                    if ($data['comid'] > 0) {
                        $commentAnswered = $this->entityManager->getRepository('Learn\Entity\Comment')->find($data['comid']);
                    }
                
                    $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')->findOneBy(array("id" => $data['tutoid']));
                    $user = $this->entityManager->getRepository('User\Entity\User')->findOneBy(array("id" => $_SESSION['id']));
    
                    $comment = new Comment();
                    $comment->setUser($user);
                    $comment->setCommentAnswered($commentAnswered);
                    $comment->setTutorial($tutorial);
                    $comment->setMessage($data['message']);
    
                    $this->entityManager->persist($comment);
                    $this->entityManager->flush();
    
                    $mailSent = mailComment("Un utilisateur a posté un commentaire.", $user, $comment, $data);

                    $arrayComment = array(
                        'id' => $comment->getId(),
                        'picture' => $comment->getUser()->getPicture(),
                        'username' => $comment->getUser()->getFirstname() . " " . $comment->getUser()->getSurname(),
                        'message' => $comment->getMessage(),
                        'date' => $comment->getUpdatedAt(),
                        'mailSent' => $mailSent
                    );
                    
                    return ["success" => true, "data" => $arrayComment];
                } catch (\Exception $e) {
                    return ["success" => false, "message" => $e->getMessage()];
                }
            },
            'update' => function ($data) {
                // This function can be accessed by post method only
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $comment = $this->entityManager->getRepository('Learn\Entity\Comment')->find($data['comid']);
                
                $comment->setMessage($data['message']);
                $comment->setUpdatedAt(new \DateTime());
                
                $user = $this->entityManager->getRepository('User\Entity\User')->find($_SESSION['id']);
                $userRegular = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(array("user" => $user));

                if ($user != $comment->getUser()) {
                    return ["error" => "Vous n'avez pas le droit de modifier ce commentaire."];
                }
                
                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                if ($userRegular->isPrivateFlag() == 1) {
                    $username = "Anonyme";
                } else {
                    $username = $user->getFirstname() . " " . $user->getSurname();
                }
                
                if ($user->getPicture() !== NULL) {
                    $picture = "/public/content/user_data/user_img/" . $user->getPicture();
                } else {
                    $picture = "/public/content/img/login.png";
                }

                mailComment("Un utilisateur a modifié un commentaire.", $user, $comment, $data);

                return  [
                    "id" => $comment->getId(),
                    "username" => $username,
                    "date" => date("\L\\e d M Y à H:i", strtotime($comment->getCreatedAt()->format('Y-m-d\TH:i:s.u'))) . " (modifié le " . date("\l\\e d M Y à H:i", time()) . " )",
                    "message" => $comment->getMessage(),
                    'picture' => $picture
                ];
            },
            'delete' => function ($data) {
                // This function can be accessed by post method only
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $comment = $this->entityManager->getRepository('Learn\Entity\Comment')->find($data['comid']);
                $this->entityManager->remove($comment);
                $this->entityManager->flush();

                return true;
            },
            'alert' => function ($data) {
                $comment = $this->entityManager->getRepository('Learn\Entity\Comment')->find(intval($data['comid']));

                $user = $this->entityManager->getRepository('User\Entity\User')->find($_SESSION['id']);

                $mail = new Mailer();
                
                $subject = "Un utilisateur a fait une demande de modération.";
                $body = "L'utilisateur " . $user->getFirstname() . " " . $user->getSurname() . " (#ID : " . $user->getId() . ") a fait une demande de modération concernant le message #" . $comment->getId() . ".</br><b>Raison :</b><br/>";
                $body .= trim(htmlspecialchars($data['message'])) . "</br></br><b>Message :</b>";
                $body .= "</br><p>" . trim(htmlspecialchars($comment->getMessage())) . "</p>";
                

                $mail->sendMail("contact@vittascience.com", $subject, $body, strip_tags($body), "fr_default", "support@vittascience.com", "Support Vittascience");
            },
            'get_from_tutorials' => function ($data) {
                $comments = $this->entityManager->getRepository('Learn\Entity\Comment')->findBy(array("tutorial" => $data['tutorial_id']));
                $arrayResult = array();
                foreach ($comments as $comment) {
                    if ($comment->getCommentAnswered() != NULL) {
                        $reply = [
                            "id" => $comment->getCommentAnswered()->getId(),
                            "user" => $comment->getCommentAnswered()->getUser(),
                            "commentAnswered" => NULL,
                            "message" => $comment->getCommentAnswered()->getMessage(),
                            "createdAt" => $comment->getCommentAnswered()->getCreatedAt(),
                            "updatedAt" => $comment->getCommentAnswered()->getUpdatedAt(),
                            "picture" => $comment->getUser()->getPicture(),
                            "username" => $comment->getUser()->getFirstname() . " " . $comment->getUser()->getSurname()
                        ];
                    } else {
                        $reply = null;
                    }
                    $result = [
                        "id" => $comment->getId(),
                        "user" => $comment->getUser()->getId(),
                        "commentAnswered" => $reply,
                        "message" => $comment->getMessage(),
                        "createdAt" => $comment->getCreatedAt(),
                        "updatedAt" => $comment->getUpdatedAt(),
                        "picture" => $comment->getUser()->getPicture(),
                        "username" => $comment->getUser()->getFirstname() . " " . $comment->getUser()->getSurname()
                    ];
                    array_push($arrayResult, $result);
                }
                return $arrayResult;
            },
            'get_multiple_users' => function () {
                $users = $this->entityManager->getRepository('User\Entity\User')->getMultipleUsers($_POST['ids']);

                $arrayResult = array();

                foreach ($users as $user) {
                    $regularUser = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(array("user" => $user));
                    if (!$regularUser) {
                        continue;
                    }

                    if ($regularUser->isPrivateFlag() == 1) {
                        $username = "Anonyme";
                    } else {
                        $username = $user->getFirstname() . " " . $user->getSurname();
                    }
                    if ($user->getPicture() !== NULL) {
                        $picture = "/public/content/user_data/user_img/" . $user->getPicture();
                    } else {
                        $picture = "/public/content/img/login.png";
                    }

                    if ($regularUser->isPrivateFlag() == 1) {
                        $result = [
                            "id" => $user->getId(),
                            "private_flag" => $regularUser->isPrivateFlag()
                        ];
                    } else {
                        $result = [
                            "id" => $user->getId(),
                            "username" => $username,
                            "picture" => $picture,
                            "private_flag" => $regularUser->isPrivateFlag()
                        ];
                    }
                    array_push($arrayResult, $result);
                }
                return  $arrayResult;
            }
        );
    }
}

function mailComment($subject, $user, $comment, $data)
{
    $mail = new Mailer();
    $subject = "Un utilisateur a posté un commentaire.";
    $body = "L'utilisateur " . $user->getFirstname() . " " . $user->getSurname() . " (#ID : " . $user->getId() . ") a fait une action sur le message #" . $comment->getId() . ".";
    $body .= "<b>Message :</b>";
    $body .= "</br><p>" . trim(htmlspecialchars($data['message'])) . "</p>";
    return $mail->sendMail("contact@vittascience.com", $subject, $body, strip_tags($body), "fr_default", "support@vittascience.com", "Support Vittascience");
}
