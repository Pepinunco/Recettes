<?php

namespace App\Controller;


use App\Form\EditFormType;
use App\Form\EditPasswordFormType;
use App\services\ProfilManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/profil")]

class ProfilController extends AbstractController
{
    private ProfilManager $manager;

    public function __construct(ProfilManager $manager)
    {
        $this->manager = $manager;
    }

    #[Route("/editProfile", name: "edit", methods: ['POST','GET'])]
    public function edit(Request $request,ProfilManager $profileManager): Response
    {
        $user = $this->getUser();
        $form = $this -> createForm ( EditFormType::class, $user );
        $form -> handleRequest ( $request );
        $ppdirectory = $this -> getParameter ('profile_picture_directory');



        if ($form -> isSubmitted () && $form -> isValid ()) {
            if($form->get('Enregistrer')->isClicked()){

            $profilePictureFile = $form -> get ( 'profilePicture' ) -> getData ();
            try {
                $profileManager -> editProfil ( $user, $profilePictureFile, $ppdirectory );

                $this -> addFlash ( 'sucess', 'Profil Modifié avec succès.' );
                return $this -> redirectToRoute ( 'app_accueil' );
            } catch (FileException $e) {
                $this -> addFlash ( 'error', ' Erreur de téléchargement de la photo: ' . $e -> getMessage () );
                return $this -> redirectToRoute ( 'app_accueil' );
            }
        }elseif ($form->get('Supprimer')->isClicked()){
                try {
                    $this->manager->deleteUtilisateur($user);
                    $request->getSession()->invalidate();
                    $this->container->get('security.token_storage')->setToken(null);
                    $this->addFlash('success', 'Utilisateur supprimé avec succès.');
                    return $this->redirectToRoute('app_homepage');
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                } catch (NotFoundExceptionInterface $e) {
                    $this->addFlash('error', $e->getMessage());
                } catch (ContainerExceptionInterface $e) {

                }
            }
        }
        return $this -> render ( 'registration/edit.html.twig', [
            'editForm' => $form -> createView (),
            'user' => $this -> getUser ()
        ] );
    }

    #[Route("/editPassword", name: "editPassword")]
    public function editPass(Request $request, ProfilManager $passwordManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditPasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword=$form->get('plainPassword')->getData();
            try{
           $passwordManager->editPassword($user, $plainPassword);

           $this->addFlash ('success', 'Mot de passe modifié avec succès.');
           return $this->redirectToRoute ('app_accueil');
            } catch (\Exception $e) {
                $this->addFlash ('error', 'Une erreur est survenue lors de la modification du mot de passe.');
            }
        }


        return $this->render('registration/editPW.html.twig', [
            'editPWForm' => $form->createView(),
        ]);
    }

    #[Route("/otherProfile/{pseudo}", name: "otherProfile", methods: ['GET'])]
    public function otherProfile(string $pseudo, ProfilManager $profilManager):Response
    {
        $utilisateur = $profilManager->getUserByPseudo ($pseudo);
        if (!$utilisateur) {
            throw $this->createNotFoundException ('Utilisateur non trouvé.');
        }

        return $this->render('profil/other_profile_template.html.twig', [
            'utilisateur' => $utilisateur
        ]);
    }
}