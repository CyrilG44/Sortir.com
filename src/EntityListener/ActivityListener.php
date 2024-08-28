<?php

namespace App\EntityListener;

use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;


class ActivityListener
{

    public function __construct(
       private readonly EntityManagerInterface $em
){}

//    public function preUpdate(Activity $activity, LifecycleEventArgs $event)
//    {
//        $this->update($activity);
//    }

    public function postLoad(Activity $activity, LifecycleEventArgs $event)
    {
        $this->update($activity);
    }

    public function update(Activity $activity): void
    {
        $duractionHours = $activity->getDurationHours();
        $startingDate = $activity->getStartingDate();
        $date = new \DateTime();
        $startingDate->modify('+'.$duractionHours.'hour');



        if($startingDate < $date->modify('-30 day')){


            $activity->setArchived(true);
               $this->em->persist($activity);
               $this->em->flush();




        }
    }



}
