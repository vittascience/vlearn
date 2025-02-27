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
                $data['id'] = !empty($data['id']) && intval($data['id']) ? $data['id'] : null;

                if (empty($data['title'])) {
                    return ['success' => false, 'message' => 'title_empty'];
                }

                $ids = [];
                try {
                    foreach ($data['resources'] as $id) {
                        $ids[] = (int)$id;
                    }
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'course_ids_invalid'];
                }

                if (empty($ids)) {
                    return ['success' => false, 'message' => 'course_ids_empty'];
                }

                try {

                    if (!empty($data['id'])) {
                        $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $user->getId()]);
                        if (!$playlist) {
                            return ['success' => false, 'message' => 'playlist_not_found'];
                        }
                    } else {
                        $playlist = new Playlist($data['title']);
                        $playlist->setCreatedAt(new \DateTime());
                        $playlist->setUser($user);
                    }

                    $playlist->setTitle($data['title']);
                    $playlist->setDescription($data['description']);
                    $playlist->setRights((int)$data['rights']);
                    $playlist->setUpdatedAt(new \DateTime());
                    $this->entityManager->persist($playlist);
                    $this->entityManager->flush();


                    //get course link playlist
                    $courseLinkPlaylist = $this->entityManager->getRepository(CourseLinkPlaylist::class)->findBy(["playlistId" => $playlist->getId()]);
                    if ($courseLinkPlaylist) {
                        foreach ($courseLinkPlaylist as $courseLink) {
                            $this->entityManager->remove($courseLink);
                        }
                        $this->entityManager->flush();
                    }

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

                $user = $this->isUserLogged();
                if (!$user) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                $data = json_decode(file_get_contents('php://input'), true);

                if (empty($data['id'])) {
                    return ['success' => false, 'message' => 'id_empty'];
                }

                try {
                    $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(["id" => $data['id'], "user" => $user->getId()]);
                    if (!$playlist) {
                        return ['success' => false, 'message' => 'playlist_not_found'];
                    }

                    $courseLinkPlaylist = $this->entityManager->getRepository(CourseLinkPlaylist::class)->findBy(["playlistId" => $playlist->getId()]);

                    if ($courseLinkPlaylist) {
                        foreach ($courseLinkPlaylist as $courseLink) {
                            $this->entityManager->remove($courseLink);
                        }
                        $this->entityManager->flush();
                    }

                    $this->entityManager->remove($playlist);
                    $this->entityManager->flush();
                    return ['success' => true, 'message' => 'playlist_deleted'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_by_id' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $data = json_decode(file_get_contents('php://input'), true);
                if (empty($data['id'])) {
                    return ['success' => false, 'message' => 'id_empty'];
                }

                try {
                    $result = $this->entityManager->getRepository(Playlist::class)->getLightPublicDataPlaylistById($data['id']);
                    if (!$result) {
                        $result = $this->entityManager->getRepository(Playlist::class)->getLightDataPlaylistById($data['id'], $this->isUserLogged());
                    }
                    if (!$result) {
                        return ['success' => false, 'message' => 'playlist_not_found'];
                    }
                    $resources = $this->entityManager->getRepository(CourseLinkPlaylist::class)->getCourseLinkPlaylistByArrayOfIds($data['id']);
                    $result['resources'] = $resources;
                    // prepare and return data
                    return ['success' => true, 'playlist' => $result];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_by_filter' => function ($data) {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $sort = !empty($_POST['filter']['sort'][0]) ? htmlspecialchars(strip_tags($_POST['filter']['sort'][0])) : '';
                $page = !empty($_POST['page']) ? htmlspecialchars(strip_tags($_POST['page'])) : 1;
                $search = !empty($_POST['filter']['search']) ? htmlspecialchars(strip_tags($_POST['filter']['search'])) : null;
                //$lang = !empty($_POST['filter']['lang']) ? htmlspecialchars(strip_tags($_POST['filter']['lang'])) : 1;
                $sanitizedFilters = $this->sanitizeAndFormatFilterParams($_POST['filter']);
                try {
                    $results = $this->entityManager->getRepository(Playlist::class)->getByFilter($sanitizedFilters, $search, $sort, $page);
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
                                $playlistLength = $this->entityManager->getRepository(Playlist::class)->getLengthOfCourseLinkPlaylistById($playlistTMP['id']);
                                if ($reuqestImg && $playlistLength) {
                                    $playlistTMP['image'] = $reuqestImg['img'];
                                    $playlistTMP['length'] = $playlistLength['length'];
                                } else {
                                    $playlistTMP['image'] = null;
                                    $playlistTMP['length'] = 0;
                                }

                                $arrayResult['playlists'][] = $playlistTMP;
                            }
                        }
                    }
                    return ['success' => true, 'results' => $arrayResult];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_all_playlists' => function () {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $data = json_decode(file_get_contents('php://input'), true);
                $data['page'] = !empty($data['page']) ? htmlspecialchars(strip_tags($data['page'])) : '';

                try {
                    $results = $this->entityManager->getRepository(Playlist::class)->getAllPlaylists($data['page']);
                    $arrayResult['pagination'] = $results['pagination'];
                    foreach ($results["items"] as $item) {
                        if (json_encode($item) != NULL && json_encode($item) != false) {
                            $playlistTMP = $item->jsonSerialize();
                            $reuqestImg = $this->entityManager->getRepository(Playlist::class)->getImageOfFirstCourseInPlaylist($playlistTMP['id']);
                            $playlistLength = $this->entityManager->getRepository(Playlist::class)->getLengthOfCourseLinkPlaylistById($playlistTMP['id']);
                            if ($reuqestImg && $playlistLength) {
                                $playlistTMP['image'] = $reuqestImg['img'];
                                $playlistTMP['length'] = $playlistLength['length'];
                            } else {
                                $playlistTMP['image'] = null;
                                $playlistTMP['length'] = 0;
                            }

                            $arrayResult['playlists'][] = $playlistTMP;
                        }
                    }
                    return ['success' => true, 'results' => $arrayResult];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            },
            'get_my_playlists' => function () {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                $data = json_decode(file_get_contents('php://input'), true);
                $data['page'] = !empty($data['page']) ? htmlspecialchars(strip_tags($data['page'])) : '';
                
                $user = $this->isUserLogged();
                if (!$user) {
                    return ['success' => false, 'message' => 'user_not_logged'];
                }

                try {
                    $results = $this->entityManager->getRepository(Playlist::class)->getMyPlaylists($data['page'], $user);
                    $arrayResult['pagination'] = $results['pagination'];
                    foreach ($results["items"] as $item) {
                        if (json_encode($item) != NULL && json_encode($item) != false) {
                            $playlistTMP = $item->jsonSerialize();
                            $reuqestImg = $this->entityManager->getRepository(Playlist::class)->getImageOfFirstCourseInPlaylist($playlistTMP['id']);
                            $playlistLength = $this->entityManager->getRepository(Playlist::class)->getLengthOfCourseLinkPlaylistById($playlistTMP['id']);
                            if ($reuqestImg && $playlistLength) {
                                $playlistTMP['image'] = $reuqestImg['img'];
                                $playlistTMP['length'] = $playlistLength['length'];
                            } else {
                                $playlistTMP['image'] = null;
                                $playlistTMP['length'] = 0;
                            }

                            $arrayResult['playlists'][] = $playlistTMP;
                        }
                    }
                    return ['success' => true, 'results' => $arrayResult];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            }
        );
    }

    private function sanitizeAndFormatFilterParams($incomingFilters)
    {
        $sanitizedFilters = [];
        if (!empty($incomingFilters["support"])) {
            $supports = [];
            foreach ($incomingFilters["support"] as $incomingSupport) {
                array_push($supports, intval($incomingSupport));
            }
            $sanitizedFilters['support'] = "(" . implode(",", $supports) . ")";
        }
        if (!empty($incomingFilters["difficulty"])) {
            $difficulties = [];
            foreach ($incomingFilters["difficulty"] as $incomingDifficulty) {
                array_push($difficulties, intval($incomingDifficulty));
            }
            $sanitizedFilters['difficulty'] = "(" . implode(",", $difficulties) . ")";
        }
        if (!empty($incomingFilters["lang"])) {
            $languages = [];
            foreach ($incomingFilters["lang"] as $incomingLang) {
                array_push($languages, "'" . htmlspecialchars(strip_tags(trim($incomingLang))) . "'");
            }
            $sanitizedFilters['lang'] = "(" . implode(",", $languages) . ")";
        }

        return $sanitizedFilters;
    }


    public function isUserLogged()
    {
        if(!isset($_SESSION['id'])){
            return false;
        }
        $user = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $_SESSION['id']]);
        if (!$user) {
            return false;
        }
        return $user;
    }
}
