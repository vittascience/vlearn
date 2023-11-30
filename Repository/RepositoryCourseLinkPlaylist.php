<?php

namespace Learn\Repository;

use User\Entity\User;
use Learn\Entity\Course;
use Doctrine\ORM\EntityRepository;
use Learn\Entity\CourseLinkPlaylist;


class RepositoryCourseLinkPlaylist extends EntityRepository
{

    public function getCourseLinkPlaylistByArrayOfIds($id) {
        // join course to get course title and updated at
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('clp.id, c.id, c.title, c.updatedAt, c.views, c.rights, u.firstname, u.surname, u.id')
            ->from(CourseLinkPlaylist::class, 'clp')
            ->leftJoin(Course::class, 'c', 'WITH', "c.id=clp.courseId")
            ->leftJoin(User::class, 'u', 'WITH', "u.id=c.user")
            ->andWhere('clp.playlistId = :id')
            ->setParameter('id', $id);
        $results = $queryBuilder->getQuery()->getResult();
        return $results;
    }

}