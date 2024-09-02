<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user',name: 'app_user')]
class UserController extends AbstractController
{

/* ====== LISTER LES USERS ========
    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
*/
//    =================== CREATE NEW USER ===================

//    #[Route('/new', name: '_new', methods: ['GET', 'POST'])]
//    public function new(Request $request, EntityManagerInterface $entityManager): Response
//    {
//        $user = new User();
//        $form = $this->createForm(UserType::class, $user);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager->persist($user);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('_index', [], Response::HTTP_SEE_OTHER);
//        }
//
//        return $this->render('user/new.html.twig', [
//            'user' => $user,
//            'form' => $form,
//        ]);
//    }

    #[Route('/myAccount', name: '_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/myAccount/edit', name: '_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('imageFile')->getData();
            if($user->getProfileImage()) {
                /** @var UploadedFile $file */
                $filename = $user->getProfileImage();
                $filePath = $this->getParameter('kernel.project_dir') . '/public/profile/images/' . $filename;
                unlink($filePath);
            }
            $filename = $file->getClientOriginalName();
            $file->move($this->getParameter('kernel.project_dir') . '/public/profile/images', $filename);
            $user->setProfileImage($filename);

            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/profile/{id}', name: '_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function organizerProfile(User $user): Response
    {

        return $this->render('user/show.html.twig', [
            'user' => $user,


        ]);
    }

//    ==================== DELETE USER ===============

//    #[Route('/delete', name: '_delete', methods: ['POST'])]
//    public function delete(Request $request, EntityManagerInterface $entityManager): Response
//    {
//        $user = $this->getUser();
//
//        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
//            $entityManager->remove($user);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('_index', [], Response::HTTP_SEE_OTHER);
//    }
}
