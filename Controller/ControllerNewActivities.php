<?php

namespace Learn\Controller;

use Learn\Entity\Activity;
use Learn\Controller\Controller;
use Classroom\Entity\Applications;

class ControllerNewActivities extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(            
            'get_all_apps' => function () {
                $Apps = $this->entityManager->getRepository(Applications::class)->findAll();
                $AppsArray = [];
                foreach ($Apps as $app) {
                    $AppsArray[] = $app->jsonSerialize();
                }
                return $AppsArray;
            },
            'create_exercice' => function ($data) {
                
                $title = !empty($data['title']) ? htmlspecialchars($data['title']) : null;
                $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                $content = !empty($data['content']) ? htmlspecialchars($data['content']) : null;
                $solution = !empty($data['solution']) ? htmlspecialchars($data['solution']) : null;
                $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                $indice = !empty($data['indice']) ? htmlspecialchars($data['indice']) : null;

                $exercice = new Activity($title, $content, $this->user, false);
                $exercice->setSolution($solution);
                $exercice->setType($type);
                $exercice->setTolerance($tolerance);
                $this->entityManager->persist($exercice);
                $this->entityManager->flush();
                return ['success' => true];
            },
            'get_one_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {
                        return $activity->jsonSerialize();
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
            'delete_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {
                        $this->entityManager->remove($activity);
                        $this->entityManager->flush();
                        return ['success' => true];
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
            'update_activity' => function ($data) {
                $id = !empty($data['id']) ? htmlspecialchars($data['id']): null;
                if ($id) {
                    $activity = $this->entityManager->getRepository(Activity::class)->find($id);
                    if ($activity) {
                        $title = !empty($data['title']) ? htmlspecialchars($data['title']) : null;
                        $type = !empty($data['type']) ? htmlspecialchars($data['type']) : null;
                        $content = !empty($data['content']) ? htmlspecialchars($data['content']) : null;
                        $solution = !empty($data['solution']) ? htmlspecialchars($data['solution']) : null;
                        $tolerance = !empty($data['tolerance']) ? htmlspecialchars($data['tolerance']) : null;
                        $activity->setTitle($title);
                        $activity->setType($type);
                        $activity->setContent($content);
                        $activity->setSolution($solution);
                        $activity->setTolerance($tolerance);
                        $this->entityManager->persist($activity);
                        $this->entityManager->flush();
                        return ['success' => true];
                    } else {
                        return ['error' => 'Activity not found'];
                    }
                } else {
                    return ['error' => 'No id provided'];
                }
            },
        );
    }
}
