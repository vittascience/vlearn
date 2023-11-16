<?php

namespace Learn\Controller;


use User\Entity\User;
use Learn\Entity\Course;
use Learn\Entity\Playlist;
use Learn\Entity\CourseLinkPlaylist;


class ControllerPlaylist extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'save_my_playlist' => function () {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ['success' => false, 'message' => 'Method not allowed'];
                }

                $user = $this->isUserLogged();
                if (!$user) {
                    return ['success' => false, 'message' => 'User not logged'];
                }

                //get the request payload
                $data = json_decode(file_get_contents('php://input'), true);
                //sanitize the data
                $data['title'] = !empty($data['title']) ? htmlspecialchars(strip_tags($data['title'])) : '';
                $data['description'] = !empty($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : '';

                if (empty($data['title'])) {
                    return ['success' => false, 'message' => 'Title is empty'];
                }

                $ids = [];
                try {
                    foreach ($data['resources'] as $id) {
                        $ids[] = (int)htmlspecialchars(strip_tags($id));
                    }
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'Course ids are not valid'];
                }

                if (empty($ids)) {
                    return ['success' => false, 'message' => 'Course ids are empty'];
                }

                try {
                    $playlist = new Playlist($data['title']);
                    $playlist->setDescription($data['description']);
                    $playlist->setUser($user);
                    $playlist->setCreatedAt(new \DateTime());
                    $playlist->setUpdatedAt(new \DateTime());
                    $playlist->setRights(0);
                    $this->entityManager->persist($playlist);
                    $this->entityManager->flush();


                    for ($i = 0; $i < count($ids); $i++) {
                        $course = $this->entityManager->getRepository(Course::class)->findOneBy(["id" => $ids[$i]]);
                        if (!$course) {
                            return ['success' => false, 'message' => 'Course not found'];
                        }

                        $courseLinkPlaylist = new CourseLinkPlaylist();
                        $courseLinkPlaylist->setCourseId($course);
                        $courseLinkPlaylist->setPlaylistId($playlist);
                        $courseLinkPlaylist->setIndexOrder($i);
                        $this->entityManager->persist($courseLinkPlaylist);
                    }
                    $this->entityManager->flush();
                    
                    return ['success' => true, 'message' => 'Playlist saved'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'delete_my_playlist' => function ($data) {

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ['success' => false, 'message' => 'Method not allowed'];
                }

                if (!$this->isUserLogged()) {
                    return ['success' => false, 'message' => 'User not logged'];
                }

                try {
                    $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $this->user->getId()]);
                    if (!$playlist) {
                        return ['success' => false, 'message' => 'Playlist not found'];
                    }
                    $this->entityManager->remove($playlist);
                    $this->entityManager->flush();
                    return ['success' => true, 'message' => 'Playlist deleted'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'update_my_playlist' => function ($data) {

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ['success' => false, 'message' => 'Method not allowed'];
                }

                if (!$this->isUserLogged()) {
                    return ['success' => false, 'message' => 'User not logged'];
                }

                try {
                    $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $this->user->getId()]);
                    if (!$playlist) {
                        return ['success' => false, 'message' => 'Playlist not found'];
                    }
                    $playlist->setTitle($data['title']);
                    $playlist->setDescription($data['description']);
                    $playlist->setUpdatedAt(new \DateTime());
                    $this->entityManager->persist($playlist);
                    $this->entityManager->flush();
                    return ['success' => true, 'message' => 'Playlist updated'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
        );
    }


    public function isUserLogged()
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
        if (!$user) {
            return false;
        }
        return $user;
    }
}
