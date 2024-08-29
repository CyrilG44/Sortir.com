<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\State;
use App\Form\ActivityType;
use App\Form\CancelActivityType;
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
    #[Route('/', name: '_list', methods: ['GET'])]
    public function index(ActivityRepository $activityRepository): Response
    {
        $allActivities =$activityRepository->findAll();

        return $this->render('activity/list.html.twig', [
            'activities' => $allActivities,
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


    }
}
