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
                    return ['success' => false, 'message' => 'method_not_allowed'];
                }

                $user = $this->isUserLogged();
                if (!$user) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                //get the request payload
                $data = json_decode(file_get_contents('php://input'), true);
                //sanitize the data
                $data['title'] = !empty($data['title']) ? htmlspecialchars(strip_tags($data['title'])) : '';
                $data['description'] = !empty($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : '';

                if (empty($data['title'])) {
                    return ['success' => false, 'message' => 'title_empty'];
                }

                $ids = [];
                try {
                    foreach ($data['resources'] as $id) {
                        $ids[] = (int)htmlspecialchars(strip_tags($id));
                    }
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'course_ids_invalid'];
                }

                if (empty($ids)) {
                    return ['success' => false, 'message' => 'course_ids_empty'];
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
                            return ['success' => false, 'message' => 'course_not_found'];
                        }

                        $courseLinkPlaylist = new CourseLinkPlaylist();
                        $courseLinkPlaylist->setCourseId($course);
                        $courseLinkPlaylist->setPlaylistId($playlist);
                        $courseLinkPlaylist->setIndexOrder($i);
                        $this->entityManager->persist($courseLinkPlaylist);
                    }
                    $this->entityManager->flush();
                    
                    return ['success' => true, 'message' => 'playlist_saved'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'delete_my_playlist' => function ($data) {

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ['success' => false, 'message' => 'method_not_allowed'];
                }

                if (!$this->isUserLogged()) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                try {
                    $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $this->user->getId()]);
                    if (!$playlist) {
                        return ['success' => false, 'message' => 'playlist_not_found'];
                    }
                    $this->entityManager->remove($playlist);
                    $this->entityManager->flush();
                    return ['success' => true, 'message' => 'playlist_deleted'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'update_my_playlist' => function ($data) {

                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    return ['success' => false, 'message' => 'method_not_allowed'];
                }

                if (!$this->isUserLogged()) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                try {
                    $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $this->user->getId()]);
                    if (!$playlist) {
                        return ['success' => false, 'message' => 'playlist_not_found'];
                    }
                    $playlist->setTitle($data['title']);
                    $playlist->setDescription($data['description']);
                    $playlist->setUpdatedAt(new \DateTime());
                    $this->entityManager->persist($playlist);
                    $this->entityManager->flush();
                    return ['success' => true, 'message' => 'playlist_updated'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_by_id' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];


                $user = $this->isUserLogged();
                if (!$user) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                $data = json_decode(file_get_contents('php://input'), true);
                $id = !empty($data['id']) ? htmlspecialchars(strip_tags($data['id'])) : '';

                try {
                    $result = $this->entityManager->getRepository(Playlist::class)->getLightDataPlaylistById($id, $user->getId());
                    if (!$result) {
                        return ['success' => false, 'message' => 'playlist_not_found'];
                    }
                    $resources = $this->entityManager->getRepository(CourseLinkPlaylist::class)->getCourseLinkPlaylistByArrayOfIds($id);
                    $result['resources'] = $resources;
                    // prepare and return data
                    return ['success' => true, 'playlist' => $result];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_by_filter' => function ($data) {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $data = json_decode(file_get_contents('php://input'), true);

                $sort = !empty($_POST['sort']) ? htmlspecialchars(strip_tags($_POST['sort'])) : '';
                $page = !empty($_POST['page']) ? htmlspecialchars(strip_tags($_POST['page'])) : 1;
                $search = !empty($_POST['filter']['search']) ? htmlspecialchars(strip_tags($_POST['filter']['search'])) : null;
                //$lang = !empty($_POST['filter']['lang']) ? htmlspecialchars(strip_tags($_POST['filter']['lang'])) : 1;
                $options = !empty($data['options']) ? $data['options'] : [];

                try {
                    $results = $this->entityManager->getRepository(Playlist::class)->getByFilter($options, $search, $sort, $page);
                    $arrayResult['pagination'] = $results['pagination'];
                    foreach ($results["items"] as $item) {
                        if (json_encode($item) != NULL && json_encode($item) != false) {
                            $resultToReturn = json_decode(json_encode(($item)));
                            if (property_exists($resultToReturn, 'folder')) {
                                $resultToReturn->forksCount = $this->entityManager->getRepository(Course::class)->getCourseForksCountAndTree($resultToReturn->id)['forksCount'];
                                $arrayResult['courses'][] =  $resultToReturn;
                            } else {
                                $playlistTMP = $item->jsonSerialize();
                                $reuqestImg = $this->entityManager->getRepository(Playlist::class)->getImageOfFirstCourseInPlaylist($playlistTMP['id']);
                                if ($reuqestImg) {
                                    $playlistTMP['image'] = $reuqestImg['img'];
                                } else {
                                    $playlistTMP['image'] = null;
                                }

                                $arrayResult['playlists'][] = $playlistTMP;
                            }
                        }
                    }
                    return ['success' => true, 'results' => $arrayResult];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            }
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
