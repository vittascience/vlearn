<?php

namespace Learn\Repository;

use User\Entity\User;
use Learn\Entity\Course;
use User\Entity\Regular;
use Learn\Entity\Activity;
use Learn\Entity\Playlist;
use Doctrine\ORM\EntityRepository;
use Learn\Entity\CourseLinkActivity;
use Learn\Entity\CourseLinkPlaylist;
use Doctrine\ORM\Tools\Pagination\Paginator;

class RepositoryPlaylist extends EntityRepository
{

    public function getByFilter($options, $search, $sort, $page = 1)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere('a.isFromClassroom=0')
            ->andWhere("
                c.title LIKE :search OR c.description LIKE :search 
                OR a.title LIKE :search OR a.content LIKE :search
            ");

        if ($options) {
            foreach ($options as $key => $option) {
                $queryBuilder->andWhere("c.$key IN $option");
            }
        }

        $sortField = "createdAt";
        $sortDirection = "DESC";
        if (!empty($sort)) {
            list($incomingSortField, $incomingSortDirection) = explode('-', $sort);
            $sortField = !empty($incomingSortField) ? $incomingSortField : "createdAt";
            $sortDirection = !empty($incomingSortDirection) ? strtoupper($incomingSortDirection) : "DESC";
        }

        $queryBuilder->setParameter('search', "%$search%");
        $results = $queryBuilder->groupBy('c.id')
            ->orderBy("c.$sortField", $sortDirection)
            ->getQuery()
            ->getResult();


        if (array_key_exists('support', $options) || array_key_exists('difficulty', $options) || $sortField != "createdAt") {
            $results2 = [];
        } else {
            $queryBuilder2 = $this->getEntityManager()->createQueryBuilder();
            $results2 = $queryBuilder2->select('p')
                ->from(Playlist::class, 'p')
                ->andWhere("p.title LIKE :search OR p.description LIKE :search")
                ->andWhere('p.rights=1 OR p.rights=2')
                ->setParameter('search', "%$search%")
                ->getQuery()
                ->getResult();
        }

        // Utilisez le Paginator pour paginer les résultats combinés
        $resultsMerged = array_merge($results, $results2);

        // sort results by createdAt when the two tables are merged
        if ($sortField == "createdAt") {
            if ($sortDirection == "DESC") {
                usort($resultsMerged, function ($a, $b) {
                    return $a->getCreatedAt() < $b->getCreatedAt();
                });
            } else {
                usort($resultsMerged, function ($a, $b) {
                    return $a->getCreatedAt() > $b->getCreatedAt();
                });
            }
        } else {
            if ($sortDirection == "DESC") {
                usort($resultsMerged, function ($a, $b) {
                    return $a->getViews() < $b->getViews();
                });
            } else {
                usort($resultsMerged, function ($a, $b) {
                    return $a->getViews() > $b->getViews();
                });
            }
        }

        // Configurez le nombre d'éléments par page
        $itemsPerPage = 25;
        $returnResults = array_slice($resultsMerged, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $paginatorData = [
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => count($resultsMerged),
                'totalPages' => ceil(count($resultsMerged) / $itemsPerPage),
            ],
            'items' => $returnResults,
        ];

        return $paginatorData;
    }

    public function getLightDataPlaylistById($id, $user)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('p.id, p.title, p.description, p.rights')
            ->from(Playlist::class, 'p')
            ->leftJoin(User::class, 'u', 'WITH', "u.id=p.id")
            ->andWhere('p.id = :id')
            ->andWhere('p.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user);
        $results = $queryBuilder->getQuery()->getOneOrNullResult();
        return $results;
    }

    
    public function getLightPublicDataPlaylistById($id)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('p.id, p.title, p.description, p.rights')
            ->from(Playlist::class, 'p')
            ->andWhere('p.id = :id')
            ->andWhere('p.rights=1 OR p.rights=2')
            ->setParameter('id', $id);
        $results = $queryBuilder->getQuery()->getOneOrNullResult();
        return $results;
    }

    public function getImageOfFirstCourseInPlaylist($id) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c.img')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkPlaylist::class, 'clp', 'WITH', "c.id=clp.courseId")
            ->andWhere('clp.playlistId = :id')
            ->andWhere('clp.indexOrder = 0')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        $results = $queryBuilder->getQuery()->getOneOrNullResult();
        return $results;
    }

    public function getLengthOfCourseLinkPlaylistById($id) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('COUNT(clp.id) as length')
            ->from(CourseLinkPlaylist::class, 'clp')
            ->andWhere('clp.playlistId = :id')
            ->setParameter('id', $id);
        $results = $queryBuilder->getQuery()->getOneOrNullResult();
        return $results;
    }

    public function getAllPlaylists($page) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $results = $queryBuilder->select('p')
            ->from(Playlist::class, 'p')
            ->andWhere('p.rights=1 OR p.rights=2')
            ->getQuery()
            ->getResult();

        // Configurez le nombre d'éléments par page
        $itemsPerPage = 25;
        $returnResults = array_slice($results, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $paginatorData = [
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => count($results),
                'totalPages' => ceil(count($results) / $itemsPerPage),
            ],
            'items' => $returnResults,
        ];

        return $paginatorData;
    }

    public function getMyPlaylists($page, $user) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $results = $queryBuilder->select('p')
            ->from(Playlist::class, 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        // Configurez le nombre d'éléments par page
        $itemsPerPage = 25;
        $returnResults = array_slice($results, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $paginatorData = [
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => count($results),
                'totalPages' => ceil(count($results) / $itemsPerPage),
            ],
            'items' => $returnResults,
        ];

        return $paginatorData;
    }
}
