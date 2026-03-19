<?php
// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository       $userRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'user_index')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userRepo->findAllOrderedByName(),
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', sprintf('Utilisateur « %s » créé.', $user->getName()));
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->em->remove($user);
            $this->em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('user_index');
    }
}
