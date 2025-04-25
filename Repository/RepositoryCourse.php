<?php

namespace Learn\Repository;


use User\Entity\User;
use Learn\Entity\Course;
use Learn\Entity\Activity;
use Doctrine\ORM\EntityRepository;
use Learn\Entity\CourseLinkActivity;
use Learn\Entity\CourseLinkPlaylist;

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
        $queryBuilder->setParameter('search', "%$search%")
            ->groupBy('c.id');
            
            
        $results = $queryBuilder->getQuery()->getResult();
        return count($results);
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
        $this->totalCourseForksCount = 0;
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
                'authorImg' => $courseFork['author_img'],
                'children' => []
            );
            array_push($courseForksToReturn, $courseToReturn);
        }
        return $courseForksToReturn;
    }

    public function getRandomResourcesByLang($lang, $number, $sort) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c.id, c.title, c.img, c.description, c.updatedAt, c.views, u.firstname, u.surname, u.id as userId')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->leftJoin(User::class, 'u', 'WITH', 'c.user=u.id')
            ->andWhere('c.lang = :lang')
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere('a.isFromClassroom=0')
            ->setParameter('lang', $lang)
            ->setMaxResults(10);

        if ($sort != null) {
            $queryBuilder->orderBy("c.$sort", 'DESC');
        }
        
        $results = $queryBuilder->getQuery()->getResult();

        $randomCoursesToReturn = [];
        if (count($results) > 0) {
            $randomCourses = array_rand($results, $number);
    
            if (!is_array($randomCourses)) {
                $randomCoursesToReturn = $results[$randomCourses];
            } else {
                foreach ($randomCourses as $randomCourse) {
                    array_push($randomCoursesToReturn, $results[$randomCourse]);
                }
            }
        }

        return $randomCoursesToReturn;
    }

    public function countResources() {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('count(DISTINCT c.id)')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere('a.isFromClassroom=0');

        $results = $queryBuilder->getQuery()->getSingleScalarResult();
        return $results;
    }
}