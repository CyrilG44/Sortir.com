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
            ->where("s.name!='cancelled'");

        if(!$criteria['withArchives']){
            $query->andwhere('a.is_archived = 0');
        }

        if($criteria['campus']){
            $query->andWhere('c.id=:campus')
                ->setParameter(':campus', $criteria['campus']->getId());
        }

        if($criteria['word']){
            $query->andWhere('a.name like :word')
                ->setParameter(':word','%'.$criteria['word'].'%');
        }

        if($criteria['startingAfter']){
            $query->andWhere('a.starting_date > :after')
                ->setParameter(':after',$criteria['startingAfter']);
        }

        if($criteria['startingBefore']){
            $query->andWhere('a.starting_date < :before')
                ->setParameter(':before',$criteria['startingBefore']);
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
