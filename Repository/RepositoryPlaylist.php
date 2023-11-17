<?php

namespace Learn\Repository;

use Doctrine\ORM\EntityRepository;

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
        $results = $queryBuilder->setFirstResult(($page - 1) * self::RESULT_PER_PAGE)
            ->setMaxResults(self::RESULT_PER_PAGE)
            ->groupBy('c.id')
            ->orderBy("c.$sortField", $sortDirection)
            ->getQuery()
            ->getResult();
        return $results;
    }
    
}