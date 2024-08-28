<?php

namespace App\EntityListener;

use App\Entity\Activity;
use App\Repository\StateRepository;

class ActivityListener
{

    public function __construct(
        private readonly StateRepository $stateRepository
    ) {}

    public function prePersist(Activity $activity) :void
    {
        # dd($activity->getState());
        $state = $this->stateRepository->findOneBy(['name' => "DRAFT"]);

        if(!$activity->getState()) {
            $activity->setState($state);
        }

    }

}