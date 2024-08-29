<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Registration;
use App\Form\ActivityType;
use App\Form\CancelActivityType;
use App\Repository\ActivityRepository;
use App\Repository\RegistrationRepository;
use App\Repository\StateRepository;
use ContainerFkQUUex\getStateRepositoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activity', name: 'app_activity')]
class ActivityController extends AbstractController
{
    #[Route('/', name: '_list', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository): Response
    {
        $allActivities =$activityRepository->findAll();
        $date = new \DateTime();

        return $this->render('activity/list.html.twig', [
            'activities' => $allActivities,
            'date' => $date,
        ]);
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

        return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cancel', name: '_cancel', methods: ['GET', 'POST'])]
    public function cancel(Request $request, Activity $activity, EntityManagerInterface $entityManager,StateRepository $stateRepository): Response
    {

        $form = $this->createForm(CancelActivityType::class,$activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $state = $stateRepository->findOneBy(['name' => 'cancelled']);
            $activity->setState($state);
            $entityManager->flush();




            return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity/_cancel.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);


        return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/signUp/{id}', name: '_signup', methods: ['GET'])]
    public function signUp(Request $request, Activity $activity, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository, ActivityRepository $ar): Response
    {

        if($this->isCsrfTokenValid('signup'.$activity->getId(), $request->query->get('token'))) {

            //controle statut activité
            if($activity->getState()->getName() !== 'open'){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. L\'activité n\'est pas ouverte !');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }

            //controle date limite d'inscription
            $date = new \DateTime();
            if($activity->getRegistrationLimitDate() < $date){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. Date d\'inscription dépassée !');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }

            //controle nombre max participants
            $nbParticipants = $ar->countParticipant($activity->getId());

            if($nbParticipants['nb'] >= $activity->getRegistrationMaxNb()){
                $this->addFlash('error', message: 'Inscription impossible sur l\'activité suivante : ' . $activity->getName() . '. Plus de place disponible !');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }

            $user = $this->getUser();

            $registration = new Registration();
            $registration->setActivity($activity);
            $registration->setUser($user);
            $registration->setRegistrationDate();

            $registrationDB = $registrationRepository->findOneBy([
                'activity' => $activity,
                'user' => $user
            ]);

            if($registrationDB === null){

                $entityManager->persist($registration);
                $entityManager->flush();

                $this->addFlash('success', message: 'Vous êtes enregistré(e) sur l\'activité suivante : ' . $activity->getName() . '.');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }

            else{
                $this->addFlash('error', message: 'Vous êtes déjà enregistré(e) sur l\'activité suivante : '.$activity->getName().'.');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }
        }
        else{
            $this->addFlash('error', message: 'Action illégale, vos papiers s\'il vous plaît !');

            return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('/unsubscribe/{id}', name: '_unsubscribe', methods: ['GET'])]
    public function unsubscribe(Request $request, Activity $activity, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository): Response
    {
        if ($this->isCsrfTokenValid('unsubscribe' . $activity->getId(), $request->query->get('token'))) {
            $user = $this->getUser();

            $registrationDB = $registrationRepository->findOneBy([
                'activity' => $activity,
                'user' => $user
            ]);

            if($registrationDB === null){
                $this->addFlash('error', message: 'Désinscription impossible ! Vous n\'êtes pas inscrit(e) sur l\'activité suivante : '.$activity->getName().'.');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);

            }
            else {
                $entityManager->remove($registrationDB);
                $entityManager->flush();

                $this->addFlash('success', message: 'Vous êtes désinscrit sur l\'activité suivante : ' . $activity->getName() . '.');

                return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
            }
        }
        else{
            $this->addFlash('error', message: 'Action illégale !');

            return $this->redirectToRoute('app_activity_list', [], Response::HTTP_SEE_OTHER);
        }
    }
}
