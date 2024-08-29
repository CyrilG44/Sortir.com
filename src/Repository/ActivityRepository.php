<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findByCriteria(array $criteria): array {
        $query = $this->createQueryBuilder('a')
            ->addselect('s')
            ->join('a.state', 's')
            ->addselect('o')
            ->join('a.organizer', 'o')
            ->addselect('c')
            ->join('o.campus', 'c')
            ->addselect('r')
            ->leftJoin('a.registrations', 'r')
            ->where('a.is_archived = :is_archived and s.name!=:cancelled')
            ->setParameter(':is_archived', $criteria['is_archived'])
            ->setParameter(':cancelled', 'cancelled');

        if($criteria['campus']){
            $query->andWhere('c.id=:campus')
                ->setParameter(':campus', $criteria['campus']);
        }

        if($criteria['words']){
            $words = $criteria['words'];
            $likeClause = 'a.name like ';
            $params = new ArrayCollection();
            for ($i=0;$i<count($words);$i++){
                if($i>0){
                    $likeClause .= ' or ';
                }
                $likeClause .= ':word'.$i;
                $params[] = new Parameter("word".$i,$words[$i]);
            }
            var_dump($likeClause);
            var_dump($params);
            $query->andWhere($likeClause)
                ->setParameters($params);
        }

        return $query->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Activity[] Returns an array of Activity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Activity
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
