<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user',name: 'app_user')]
class UserController extends AbstractController
{


    #[Route('/', name: '_list', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

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
    public function edit(Request $request, EntityManagerInterface $entityManager,UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('imageFile')->getData();
            if($file){
                if($user->getProfileImage()) {
                    /** @var UploadedFile $file */
                    $filename = $user->getProfileImage();
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/profile/images/' . $filename;
                    unlink($filePath);
                }
                $filename = $file->getClientOriginalName();
                $file->move($this->getParameter('kernel.project_dir') . '/public/profile/images', $filename);
                $user->setProfileImage($filename);
            }

            // encode the plain password
            if($form->get('password')->getData()){
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
            }
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

    #[Route('/delete/{id}', name: '_delete', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(Request $request, EntityManagerInterface $entityManager,int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->get('token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/disable/{id}', name: '_disable', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN")]
    public function disable(Request $request, EntityManagerInterface $entityManager,int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($this->isCsrfTokenValid('disable'.$user->getId(), $request->get('token'))) {
            if($user->isActive()){
                $user->setActive(false);
                $entityManager->flush();
                return $this->redirectToRoute('app_user_list');
            } else {
                $user->setActive(true);
                $entityManager->flush();
                return $this->redirectToRoute('app_user_list');
            }
        }
        return $this->redirectToRoute('app_user_list');
    }
}
