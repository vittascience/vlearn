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
                $commentAnswered = $this->entityManager->getRepository('Learn\Entity\Comment')
                    ->find($data['comid']);
                $tutorial = $this->entityManager->getRepository('Learn\Entity\Course')
                    ->find($data['tutoid']);
                $comment = new Comment();
                $comment->setUser($this->user);
                $comment->setCommentAnswered($commentAnswered);
                $comment->setTutorial($tutorial);
                $comment->setMessage($data['message']);
                $this->entityManager->persist($comment);
                $this->entityManager->flush();
                $arrayComment = array(
                    'id' => $comment->getId(),
                    'picture' => $comment->getUser()->getPicture(),
                    'username' => $comment->getUser()->getFirstname() . " " . $comment->getUser()->getSurname(),
                    'message' => $comment->getMessage(),
                    'date' => $comment->getUpdatedAt(),
                );
                mailComment("Un utilisateur a posté un commentaire.", $this->user, $comment, $data);
                return  $arrayComment;
            },
            'update' => function ($data) {
                // This function can be accessed by post method only
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $comment = $this->entityManager->getRepository('Learn\Entity\Comment')
                    ->find($data['comid']);
                $comment->setMessage($data['message']);
                $comment->setUpdatedAt(new \DateTime());

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                if ($this->user->isPrivateFlag() == 1) {
                    $username = "Anonyme";
                } else {
                    $username = $this->user->getFirstname() . " " . $this->user->getSurname();
                }
                if ($this->user->getPicture() !== NULL) {
                    $picture = "/public/content/user_data/user_img/" . $this->user->getPicture();
                } else {
                    $picture = "/public/content/img/login.png";
                }
                mailComment("Un utilisateur a modifié un commentaire.", $this->user, $comment, $data);
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
                $mail = new Mailer();
                $subject = "Un utilisateur a fait une demande de modération.";
                $body = "L'utilisateur " . $this->user->getFirstname() . " " . $this->user->getSurname() . " (#ID : " . $this->user['id'] . ")
    a fait une demande de modération concernant le message #" . $comment->getId() . ".</br><b>Raison :</b><br/>";
                $body .= trim(htmlspecialchars($data['message'])) . "</br></br><b>Message :</b>";
                $body .= "</br><p>" . $comment['message'] . "</p>";

                $mail->sendMail("contact@vittascience.com", $subject, $body, strip_tags($body));
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
                            "updatedAt" => $comment->getCommentAnswered()->getUpdatedAt()
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
                        "updatedAt" => $comment->getUpdatedAt()
                    ];
                    array_push($arrayResult, $result);
                }
                return  $arrayResult;
            },
            'get_multiple_users' => function () {
                $users = $this->entityManager->getRepository('User\Entity\User')->getMultipleUsers($_GET['ids']);
                $arrayResult = array();
                foreach ($users as $user) {
                    if ($user->isPrivateFlag() == 1) {
                        $username = "Anonyme";
                    } else {
                        $username = $user->getFirstname() . " " . $user->getSurname();
                    }
                    if ($user->getPicture() !== NULL) {
                        $picture = "/public/content/user_data/user_img/" . $user->getPicture();
                    } else {
                        $picture = "/public/content/img/login.png";
                    }
                    $result = [
                        "user_is_deleted" => $user->isDeleted(),
                        "id" => $user->getId(),
                        "username" => $username,
                        "picture" => $picture,
                        "private_flag" => $user->isPrivateFlag()
                    ];
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
    $body = "L'utilisateur " . $user->getFirstname() . " " . $user->getSurname() . " (#ID : " . $user->getId() . ")
a modifié le message #" . $comment->getId() . ".";
    $body .= "<b>Message :</b>";
    $body .= "</br><p>" . trim(htmlspecialchars($data['message'])) . "</p>";

    $mail->sendMail("contact@vittascience.com", $subject, $body, strip_tags($body));
}
