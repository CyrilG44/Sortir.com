<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\CampusRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activity', name: 'app_activity')]
class ActivityController extends AbstractController
{
    #[Route('/{withArchives}', name: '_list',requirements: ['withArchives' => 'true'])]
    public function index(ActivityRepository $activityRepository, CampusRepository $campusRepository, Request $request, bool $withArchives = false): Response
    {


        $criteria = $request->getPayload()->all(); //filled only in case of POST
        $criteria['withArchives'] = $withArchives;
        $criteria['campus'] = array_key_exists('campus',$criteria) ? $campusRepository->find($criteria['campus'])  : null; //$criteria['campus']>0?$criteria['campus']:null
        $criteria['word'] = array_key_exists('word',$criteria) ? strlen($criteria['word'])>0?$criteria['word']:null : null;
        $criteria['startingBefore'] = array_key_exists('startingBefore',$criteria) ? $criteria['startingBefore']  : null;
        $criteria['startingAfter'] = array_key_exists('startingAfter',$criteria) ? $criteria['startingAfter']  : null;
        $criteria['organizer'] = array_key_exists('organizer',$criteria) ? $criteria['organizer'] : null;
        $criteria['registered'] = array_key_exists('registered',$criteria) ? $criteria['registered'] : null;
        $criteria['forthcoming'] = array_key_exists('forthcoming',$criteria) ? $criteria['forthcoming'] : null;
        $criteria['ongoing'] = array_key_exists('ongoing',$criteria) ? $criteria['ongoing'] : null;
        $criteria['done'] = array_key_exists('done',$criteria) ? $criteria['done'] : null;

        $activitiesPreFilter = $activityRepository->findByCriteria($criteria);

        $criteria['startingAfter'] = $criteria['startingAfter'] ? new \DateTime($criteria['startingAfter']):null;
        $criteria['startingBefore']= $criteria['startingBefore'] ? new \DateTime($criteria['startingBefore']):null;
        $attributes = ['campusList' => $campusRepository->findAll(), 'criteria' => $criteria];

        //if none filter leading to sublist
        if(!$criteria['organizer'] and !$criteria['registered'] and !$criteria['forthcoming'] and !$criteria['ongoing'] and !$criteria['done']) {
            return $this->render('activity/list.html.twig', array_merge($attributes,[
                'activities' => $activitiesPreFilter,
            ]));
        }

        //else - if at least one filter then sublist to merge at the end
        $finalList = [];
        if($criteria['organizer']){
            $finalList = array_merge($finalList,array_filter($activitiesPreFilter, fn($a) => $a->getOrganizer()->getId()==$this->getUser()->getId()));
        }
        if($criteria['registered']){
            $tempList = array_filter($activitiesPreFilter, function($a) {
                if(count($a->getRegistrations())>0){
                    foreach($a->getRegistrations() as $registration){
                        if($registration->getUser()->getId()==$this->getUser()->getId()){
                            return true;
                        }
                    }
                }
                return false;
            });
            $finalList = array_merge($finalList,$tempList);
        }
        if($criteria['forthcoming']){
            $finalList = array_merge($finalList,array_filter($activitiesPreFilter, fn($a) => in_array($a->getState()->getName(),['draft','open','full','pending'])));
        }
        if($criteria['ongoing']){
            $finalList = array_merge($finalList,array_filter($activitiesPreFilter, fn($a) => $a->getState()->getName()=='ongoing'));
        }
        if($criteria['done']){
            $finalList = array_merge($finalList,array_filter($activitiesPreFilter, fn($a) => $a->getState()->getName()=='done'));
        }
        return $this->render('activity/list.html.twig', array_merge($attributes,[
            'activities' => array_map(fn($a)=>unserialize($a),array_unique(array_map(fn($a)=>serialize($a),$finalList))),
        ]));

    }

    #[Route('/create', name: '_create', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/create.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: '_detail', methods: ['GET'])]
    public function show(Activity $activity): Response
    {
        return $this->render('activity/detail.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/{id}/edit', name: '_update', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/update.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: '_delete', methods: ['POST'])]
    public function delete(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activity->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($activity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cancel', name: '_cancel', methods: ['GET', 'POST'])]
    public function cancel(Request $request, Activity $activity, EntityManagerInterface $entityManager,StateRepository $stateRepository): Response
    {
        $state = $stateRepository->findOneBy(['name' => 'cancelled']);
        $activity->setState($state);
        $entityManager->flush();


        return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
    }
}
