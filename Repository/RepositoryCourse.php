<?php

namespace Learn\Repository;


use Doctrine\ORM\EntityRepository;
use Learn\Entity\Course;
use Learn\Entity\Activity;
use Learn\Entity\CourseLinkActivity;
use User\Entity\User;

class RepositoryCourse extends EntityRepository
{
    const RESULT_PER_PAGE = 25;
    const PRIVATE_RIGHTS = 0;
    const UNLISTED_RIGHTS = 3;
    private $totalCourseForksCount = 0;
    // Add dql stuff.

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

    public function countByFilter($options, $search)
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
        $queryBuilder->setParameter('search', "%$search%");
        $results = $queryBuilder->groupBy('c.id')
            ->getQuery()
            ->getSingleScalarResult();

        return intval($results);
    }

    public function getCoursesSortedBy($doctrineProperty, $orderByValue)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $results = $queryBuilder->select('c')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere('a.isFromClassroom=0')
            ->orderBy("c.$doctrineProperty", "$orderByValue")
            ->getQuery()
            ->getResult();
        return $results;
    }

    public function getCourseForksCountAndTree($tutorialId)
    {
        $tree = $this->buildChildren($this->getCourseForks($tutorialId));
        return array(
            'forksCount' => $this->totalCourseForksCount,
            'tree' => $tree
        );
    }

    public function buildChildren($children)
    {
        if (count($children) > 0) {
            $this->totalCourseForksCount  += count($children);
            for ($i = 0; $i < count($children); $i++) {
                if ($this->getCourseForks($children[$i]['id'])) {
                    $children[$i]['children'] = $this->buildChildren($this->getCourseForks($children[$i]['id']));
                }
            }
        }
        return $children;
    }

    public function getCourseForks($tutorialId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $courseForks = $qb->select("
                 c.id,c.title,
                 CONCAT(u.firstname, ' ', u.surname) AS author,
                 u.picture AS author_img
             ")
            ->from(Course::class, 'c')
            ->leftJoin(User::class, 'u', 'WITH', 'c.user=u.id')
            ->andWhere('c.fork = :tutorialId')
            ->setParameter('tutorialId', $tutorialId)
            ->getQuery()
            ->getResult();

        $courseForksToReturn = [];
        foreach ($courseForks as $courseFork) {
            $courseToReturn = array(
                'id' => $courseFork['id'],
                'title' => $courseFork['title'],
                'author' => $courseFork['author'],
                'author_img' => $courseFork['author_img'],
                'children' => []
            );
            array_push($courseForksToReturn, $courseToReturn);
        }
        return $courseForksToReturn;
    }
}

// $filtered_chars = array(
//     "Š" => "S", "š" => "s",
//     "Ž" => "Z", "ž" => "z",
//     "À" => "A", "Á" => "A", "Â" => "A", "Ã" => "A", "Ä" => "A", "Å" => "A", "Æ" => "A", "à" => "a", "á" => "a", "â" => "a", "ã" => "a", "ä" => "a", "å" => "a", "æ" => "a",
//     "Ç" => "C", "ç" => "c",
//     "È" => "E", "É" => "E", "Ê" => "E", "Ë" => "E", "è" => "e", "é" => "e", "ê" => "e", "ë" => "e",
//     "Ì" => "I", "Í" => "I", "Î" => "I", "Ï" => "I", "ì" => "i", "í" => "i", "î" => "i", "ï" => "i",
//     "Ñ" => "N", "ñ" => "n",
//     "Ò" => "O", "Ó" => "O", "Ô" => "O", "Õ" => "O", "Ö" => "O", "Ø" => "O", "ð" => "o", "ò" => "o", "ó" => "o", "ô" => "o", "õ" => "o", "ö" => "o", "ø" => "o",
//     "Ù" => "U", "Ú" => "U", "Û" => "U", "Ü" => "U", "ù" => "u", "ú" => "u", "û" => "u",
//     "ý" => "y", "þ" => "b", "ÿ" => "y"
// );