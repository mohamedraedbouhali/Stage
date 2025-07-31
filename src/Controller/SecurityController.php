<?php

namespace App\Controller;

use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use VictorPrdh\RecaptchaBundle\Form\ReCaptchaType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        /*if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }*/

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();


        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('captcha', Recaptcha3Type::class, [
            'constraints' => new Recaptcha3(),
            'action_name' => 'login',
        ]);
    }


    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Handle profile update
        if ($request->isMethod('POST') && !$request->request->get('changePassword') && !$request->request->get('removePicture')) {
            $user->setName($request->request->get('name'));
            $email = $request->request->get('email');
            if ($email) {
                $user->setEmail($email);
            }
            if ($request->files->get('profilePicture')) {
                $file = $request->files->get('profilePicture');
                $filename = uniqid().'.'.$file->guessExtension();
                $file->move($this->getParameter('kernel.project_dir').'/public/uploads/profile', $filename);
                $user->setProfilePicture($filename);
            }
            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('profile');
        }
        // Remove password change logic from here (will move to a separate route)
        // Handle remove profile picture
        if ($request->isMethod('POST') && $request->request->get('removePicture')) {
            if ($user->getProfilePicture()) {
                $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/profile/'.$user->getProfilePicture();
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $user->setProfilePicture(null);
                $em->flush();
                $this->addFlash('success', 'Profile picture removed successfully!');
            }
            return $this->redirectToRoute('profile');
        }
        return $this->render('profile.html.twig', [
            'user' => $user
        ]);
    }


    #[Route('/change-password', name: 'change_password')]
    public function changePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('currentPassword');
            $newPassword = $request->request->get('newPassword');
            $confirmPassword = $request->request->get('confirmPassword');
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match.');
            } elseif (strlen($newPassword) < 6) {
                $this->addFlash('error', 'New password must be at least 6 characters.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $em->flush();
                $this->addFlash('success', 'Password updated successfully!');
                return $this->redirectToRoute('rh_dashboard');
            }
        }
        return $this->render('change_password.html.twig');
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
