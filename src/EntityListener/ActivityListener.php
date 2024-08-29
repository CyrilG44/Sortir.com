<?php

namespace App\EntityListener;

use App\Entity\Activity;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;


class ActivityListener
{

    public function __construct(
        private readonly StateRepository $stateRepository,
        private readonly EntityManagerInterface $em
    ) {}

    public function prePersist(Activity $activity) :void
    {
        $state = $this->stateRepository->findOneBy(['name' => "draft"]);

        if(!$activity->getState()) {
            $activity->setState($state);
        }
    }

    public function postLoad(Activity $activity, LifecycleEventArgs $event):void
    {
        $this->update($activity);
    }

    public function update(Activity $activity): void
    {
        if($activity->getDurationHours()){
            $durationHours = $activity->getDurationHours();
        }else{
            $durationHours = 0;
        }

       $startingDate = Clone $activity->getStartingDate();
        $date = new \DateTime();
        $startingDate->modify('+'.$durationHours.'hour');

        if($startingDate < $date->modify('-30 day')&& $activity->isArchived() == false ){

            $activity->setArchived(true);
               $this->em->persist($activity);
               $this->em->flush();
        }
    }
}
