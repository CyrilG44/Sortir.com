<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Registration;
use App\Form\ActivityType;
use App\Form\CancelActivityType;
use App\Repository\ActivityRepository;
use App\Repository\RegistrationRepository;
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
        $activity->setOrganizer($this->getUser());
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('app_activity_list', []);
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

    #[Route('/update/{id}', name: '_update', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activity $activity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('Sortie Modifié avec succès !', 'Une sortie a été mise à jour !');

            return $this->redirectToRoute('app_activity_list');
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

        return $this->redirectToRoute('app_activity_list', []);
    }

    #[Route('/{id}/cancel', name: '_cancel', methods: ['GET', 'POST'])]
    public function cancel(Request $request, Activity $activity, EntityManagerInterface $entityManager,StateRepository $stateRepository): Response
    {
        if($activity->getState()->getId() == 1 || $activity->getState()->getId() == 2 || $activity->getState()->getId() == 3){


        $form = $this->createForm(CancelActivityType::class,$activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            $state = $stateRepository->findOneBy(['name' => 'cancelled']);
            $activity->setState($state);
            $entityManager->flush();
            $this->addFlash('success', "Success L'activité a bien été cancel" );
            return $this->redirectToRoute('app_activity_list', []);
        }

        return $this->render('activity/_cancel.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
        }else {
            return $this->redirectToRoute('app_activity_list', []);
        }
    }

    #[Route('/signUp/{id}', name: '_signup', methods: ['GET'])]
    public function signUp(Request $request, Activity $activity, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository, ActivityRepository $ar, StateRepository $sr): Response
    {

        if($this->isCsrfTokenValid('signup'.$activity->getId(), $request->query->get('token'))) {

            //controle statut activité
            if($activity->getState()->getName() !== 'open'){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. L\'activité n\'est pas ouverte !');

                return $this->redirectToRoute('app_activity_list', []);
            }

            //controle date limite d'inscription
            $date = new \DateTime();
            if($activity->getRegistrationLimitDate() < $date){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. Date d\'inscription dépassée !');

                return $this->redirectToRoute('app_activity_list', []);
            }

            //controle nombre max participants
            $nbParticipants = $ar->countParticipant($activity->getId());

            if($nbParticipants['nb'] >= $activity->getRegistrationMaxNb()){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. Plus de place disponible !');

                return $this->redirectToRoute('app_activity_list', []);
            }

            $user = $this->getUser();

            $registration = new Registration();
            $registration->setActivity($activity);
            $registration->setUser($user);
            $registration->setRegistrationDate();

            //on controle si l'user est déjà inscrit
            $registrationDB = $registrationRepository->findOneBy([
                'activity' => $activity,
                'user' => $user
            ]);
            //pas inscrit
            if($registrationDB === null){

                //on l'inscrit
                $entityManager->persist($registration);
                $entityManager->flush();

                $this->addFlash('success', message: 'Vous êtes enregistré(e) sur l\'activité suivante : ' . $activity->getName() . '.');

                //controle -> si max participants atteint on change le status
                $nbParticipants = $ar->countParticipant($activity->getId());
                if($nbParticipants['nb'] >= $activity->getRegistrationMaxNb()){
                    $state = $sr->findOneBy(['name' => 'full']);
                    $activity->setState($state);
                    $entityManager->flush();
                }

                return $this->redirectToRoute('app_activity_list', []);
            }
            //déjà inscrit -> message d'erreur
            else{
                $this->addFlash('error', message: 'Vous êtes déjà enregistré(e) sur l\'activité suivante : '.$activity->getName().'.');

                return $this->redirectToRoute('app_activity_list', []);
            }
        }
        //tentative frauduleuse de magouillage -> message d'erreur
        else{
            $this->addFlash('error', message: 'Action illégale, vos papiers s\'il vous plaît !');

            return $this->redirectToRoute('app_activity_list', []);
        }
    }

    #[Route('/unsubscribe/{id}', name: '_unsubscribe', methods: ['GET'])]
    public function unsubscribe(Request $request, Activity $activity, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository, ActivityRepository $ar, StateRepository $sr): Response
    {

        if ($this->isCsrfTokenValid('unsubscribe' . $activity->getId(), $request->query->get('token'))) {
            $user = $this->getUser();

            $registrationDB = $registrationRepository->findOneBy([
                'activity' => $activity,
                'user' => $user
            ]);

            if($registrationDB === null){
                $this->addFlash('error', message: 'Désinscription impossible ! Vous n\'êtes pas inscrit(e) sur l\'activité suivante : '.$activity->getName().'.');

                return $this->redirectToRoute('app_activity_list', []);

            }
            else {
                $entityManager->remove($registrationDB);
                $entityManager->flush();

                //controle -> si nb participants < max participants
                $nbParticipants = $ar->countParticipant($activity->getId());
                if($nbParticipants['nb'] < $activity->getRegistrationMaxNb()){
                    $state = $sr->findOneBy(['name' => 'open']);
                    $activity->setState($state);
                    $entityManager->flush();
                }

                $this->addFlash('success', message: 'Vous êtes désinscrit sur l\'activité suivante : ' . $activity->getName() . '.');

                return $this->redirectToRoute('app_activity_list', []);
            }
        }
        else{
            $this->addFlash('error', message: 'Action illégale !');

            return $this->redirectToRoute('app_activity_list', []);
        }
    }

}
