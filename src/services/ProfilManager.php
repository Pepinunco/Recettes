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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfilManager
{
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private SluggerInterface $slugger;
    private UserPasswordHasherInterface $passwordEncoder;
    private UtilisateurRepository $utilisateurRepository;
    private Security $security;


    public function __construct(EntityManagerInterface $entityManager, Filesystem $filesystem, SluggerInterface $slugger, UserPasswordHasherInterface $passwordEncoder, UtilisateurRepository $utilisateurRepository, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->slugger = $slugger;
        $this->passwordEncoder = $passwordEncoder;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->security = $security;
    }

    public function editProfil(Utilisateur $user, UploadedFile $profilePictureFile=null, )
    {
        if ($profilePictureFile) {
            $newFileName = $this -> uploadProfilePicture ( $profilePictureFile, $user);
            $user -> setPhotoProfil ( $newFileName );
        }

        $this -> entityManager -> persist ( $user );
        $this -> entityManager -> flush ();

        return true;
    }


    private function uploadProfilePicture(UploadedFile $profilePictureFile, Utilisateur $user, $ppDirectory)
    {


            $originalFilename = pathinfo($profilePictureFile->getClientOriginalName (), PATHINFO_FILENAME);
            $safeFileName = $this->slugger->slug($originalFilename);
            $newFileName = $safeFileName . "-" . uniqid () . '.' .$profilePictureFile->guessExtension ();
            try {
                $profilePictureFile->move (
                    $this->getParameter('profile_picture_directory'),
                    $newFileName
                );
                if ($user->getPhotoProfil()) {
                    $oldPhotoPath = $this->getParameter('photo_profil_directory') . '/' . $user->getPhotoProfil ();
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

    public function deleteUtilisateur(int $id): void
    {
        $user = $this->entityManager->getRepository (Utilisateur::class)->find ($id);

        if (!$user) {
            throw new \Exception('Utilisateur non trouvé.');
        }

        if (!$this->security->isGranted('DELETE', $user)) {
            throw new AccessDeniedException('Accès refusé.');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}