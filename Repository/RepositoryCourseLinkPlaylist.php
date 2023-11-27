<?php

namespace Learn\Repository;

use Learn\Entity\Course;
use Doctrine\ORM\EntityRepository;
use Learn\Entity\CourseLinkPlaylist;

class RepositoryCourseLinkPlaylist extends EntityRepository
{

    public function getCourseLinkPlaylistByArrayOfIds($id) {
        // join course to get course title and updated at
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('clp.id, c.id, c.title, c.updatedAt')
            ->from(CourseLinkPlaylist::class, 'clp')
            ->leftJoin(Course::class, 'c', 'WITH', "c.id=clp.courseId")
            ->andWhere('clp.playlistId = :id')
            ->setParameter('id', $id);
        $results = $queryBuilder->getQuery()->getResult();
        return $results;
    }

}