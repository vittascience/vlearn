<?php

namespace Learn\Repository;


use Doctrine\ORM\EntityRepository;
use Learn\Entity\Course;
use Learn\Entity\Activity;
use Learn\Entity\CourseLinkActivity;

class RepositoryCourse extends EntityRepository
{
    const RESULT_PER_PAGE = 25;
    const PRIVATE_RIGHTS = 0;
    const UNLISTED_RIGHTS = 3;
    // Add dql stuff.

    public function getByFilter($options, $search, $page = 1)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere("
                c.title LIKE $search OR c.description LIKE $search 
                OR a.title LIKE $search OR a.content LIKE $search
            ");

        if($options){
            foreach ($options as $key => $option) {
                $queryBuilder->andWhere("c.$key IN $option");
            }
        }
       
        $results = $queryBuilder->setFirstResult(($page - 1) * self::RESULT_PER_PAGE)
                                ->setMaxResults(self::RESULT_PER_PAGE)
                                ->groupBy('c.id')
                                ->orderBy("c.createdAt", 'DESC')
                                ->getQuery()
                                ->getResult();
        return $results;

        // @ToBeRemoved
        // @Naser
        // last check 21/06/2022
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

        // $search_filtered = strtr($search, $filtered_chars);

        // $queryBuilder = $this->getEntityManager()
        //     ->createQueryBuilder();
        //     $queryBuilder->select('t')
        //     ->from(Course::class, 't')
        //     ->where('t.rights=1 OR t.rights=2')
        //     ->andWhere('t.title LIKE ' . $search . ' OR t.description LIKE ' . $search)
        //     /* ->orWhere('t.title LIKE ' . $search_filtered . ' OR t.description LIKE ' . $search_filtered) */;
        // foreach ($options as $key => $option) {
        //     if (count($option) > 0) {
        //         $stringArray = '(';
        //         $index = 0;
        //         foreach ($option as $data) {
        //             if ($key == 'lang') {
        //                 $data = "'" . $data . "'";
        //             }

        //             if ($index == 0) {
        //                 $stringArray .= $data;
        //             } else {
        //                 $stringArray .= ',' . $data;
        //             }
        //             $index++;
        //         }
        //         $stringArray .= ')';
        //         $queryBuilder->andWhere('t.' . $key . ' IN ' . $stringArray);
        //     }
        // }
        // $queryBuilder->setFirstResult(($page - 1) * self::RESULT_PER_PAGE);
        // $queryBuilder->setMaxResults(self::RESULT_PER_PAGE);
        // $queryBuilder->orderBy("t.createdAt", 'DESC');
        // $query = $queryBuilder->getQuery();
        // return $query->getResult();
    }

    public function countByFilter($options,$search)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('c')
            ->from(Course::class, 'c')
            ->leftJoin(CourseLinkActivity::class, 'cla', 'WITH', "c.id=cla.course")
            ->leftJoin(Activity::class, 'a', 'WITH', "a.id=cla.activity")
            ->andWhere('c.rights=1 OR c.rights=2')
            ->andWhere("
                c.title LIKE $search OR c.description LIKE $search 
                OR a.title LIKE $search OR a.content LIKE $search
            ");

        if($options){
            foreach ($options as $key => $option) {
                $queryBuilder->andWhere("c.$key IN $option");
            }
        }
        
        $results = $queryBuilder->groupBy('c.id')
                                ->getQuery()
                                ->getResult();

        return count($results);
        // @ToBeRemoved
        // @Naser
        // last check 21/06/2022
        // $queryBuilder = $this->getEntityManager()
        //     ->createQueryBuilder();
        //     $queryBuilder->select($queryBuilder->expr()->count('t.id'))
        //     ->from(Course::class, 't')
        //     ->where('t.rights=1 OR t.rights=2')
        //     ->andWhere('t.title LIKE ' . $search . ' OR t.description LIKE ' . $search);
        // foreach ($options as $key => $option) {
        //     if (count($option) > 0) {
        //         $stringArray = '(';
        //         $index = 0;
        //         foreach ($option as $data) {
        //             if ($key == 'lang') {
        //                 $data = "'" . $data . "'";
        //             }
        //             if ($index == 0) {
        //                 $stringArray .= $data;
        //             } else {
        //                 $stringArray .= ',' . $data;
        //             }
        //             $index++;
        //         }
        //         $stringArray .= ')';
        //         $queryBuilder->andWhere('t.' . $key . ' IN ' . $stringArray);
        //     }
        // }
        // $query = $queryBuilder->getQuery();

        // return intVal($query->getSingleScalarResult());
    }

    public function getCoursesSortedBy($doctrineProperty, $orderByValue)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $results = $queryBuilder->select('c')
            ->from(Course::class, 'c')
            ->where('c.rights=1 OR c.rights=2')
            ->orderBy("c.$doctrineProperty", "$orderByValue")
            ->getQuery()
            ->getResult();
        return $results;
    }
}
