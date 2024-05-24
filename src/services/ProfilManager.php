<?php

namespace App\services;

use App\Entity\Utilisateur;
use App\Form\EditPasswordFormType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilManager
{
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private SluggerInterface $slugger;
    private UserPasswordHasherInterface $passwordEncoder;
    private UtilisateurRepository $utilisateurRepository;
    private Security $security;

    private KernelInterface $kernel;


    public function __construct(KernelInterface $kernel,EntityManagerInterface $entityManager, Filesystem $filesystem, SluggerInterface $slugger, UserPasswordHasherInterface $passwordEncoder, UtilisateurRepository $utilisateurRepository, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->slugger = $slugger;
        $this->passwordEncoder = $passwordEncoder;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->security = $security;
        $this->kernel = $kernel;
    }

    public function editProfil(Utilisateur $user, UploadedFile $profilePictureFile=null, $ppdirectory)
    {
        if ($profilePictureFile) {
            $newFileName = $this -> uploadProfilePicture ( $profilePictureFile, $user, $ppdirectory);
            $user -> setPhotoProfil ( $newFileName );
        }

        $this -> entityManager -> persist ( $user );
        $this -> entityManager -> flush ();

        return true;
    }


    public function uploadProfilePicture(UploadedFile $profilePictureFile, Utilisateur $user, $ppdirectory)
    {


            $originalFilename = pathinfo($profilePictureFile->getClientOriginalName (), PATHINFO_FILENAME);
            $safeFileName = $this->slugger->slug($originalFilename);
            $newFileName = $safeFileName . "-" . uniqid () . '.' .$profilePictureFile->guessExtension ();
            try {
                $profilePictureFile->move (
                    $ppdirectory,
                    $newFileName
                );
                if ($user->getPhotoProfil()) {
                    $oldPhotoPath = $ppdirectory . '/' . $user->getPhotoProfil ();
                    if ($this->filesystem->exists($oldPhotoPath)) {
                        $this->filesystem->remove ($oldPhotoPath);
                    }
                }

                return$newFileName;
            } catch (FileException $e) {
                throw new FileException('Error uploadind photo: ' . $e->getMessage ());
            }
    }


    public function editPassword( Utilisateur $user, string $plainPassword)
    {
        $user->setPassword($this->passwordEncoder->hashPassword($user, $plainPassword));
        $this->entityManager->flush ();

        return true;
    }

    public function getUserByPseudo(string $pseudo): ?Utilisateur
    {
        return $this->utilisateurRepository->findOneBy(['Pseudo' => $pseudo]);
    }

    public function deleteUtilisateur(Utilisateur $user): void
    {


        if (!$user) {
            throw new \Exception('Utilisateur non trouvé.');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

}