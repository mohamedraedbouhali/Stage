<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Opportunity;
use App\Repository\OpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Twig\Environment;

class RHDashboardController extends AbstractController
{
    #[Route('/rh/dashboard', name: 'rh_dashboard')]
    public function index(OpportunityRepository $opportunityRepository, \App\Repository\UserRepository $userRepository): Response
    {
        $opportunities = $opportunityRepository->findAll();
        $currentUser = $this->getUser();
        $chatUsers = $userRepository->createQueryBuilder('u')
            ->where('u != :me')
            ->setParameter('me', $currentUser)
            ->getQuery()->getResult();
        $otherUser = $chatUsers ? $chatUsers[0] : null;
        return $this->render('rh_dashboard.html.twig', [
            'opportunities' => $opportunities,
            'chatUsers' => $chatUsers,
            'otherUser' => $otherUser
        ]);
    }

    #[Route('/rh/add-opportunity', name: 'rh_add_opportunity', methods: ['POST'])]
    public function addOpportunity(Request $request, EntityManagerInterface $em): Response
    {
        $opportunity = new Opportunity();
        $opportunity->setName($request->request->get('opportunityName'));
        $opportunity->setCompany($request->request->get('companyName'));
        $opportunity->setContact($request->request->get('contactPerson'));
        $opportunity->setValue((int)$request->request->get('opportunityValue'));
        $opportunity->setProbability((int)$request->request->get('probability'));
        $opportunity->setStatus($request->request->get('status'));
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate')));
        $opportunity->setDescription($request->request->get('description'));
        $opportunity->setCreatedBy($this->getUser());
        $em->persist($opportunity);
        $em->flush();
        $this->addFlash('success', 'Opportunité ajoutée avec succès !');
        return $this->redirectToRoute('rh_dashboard');
    }

    #[Route('/rh/delete-opportunity/{id}', name: 'rh_delete_opportunity', methods: ['POST'])]
    public function deleteOpportunity(int $id, OpportunityRepository $opportunityRepository, EntityManagerInterface $em): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if ($opportunity) {
            $em->remove($opportunity);
            $em->flush();
            $this->addFlash('success', 'Opportunité supprimée avec succès !');
        } else {
            $this->addFlash('error', "L'opportunité n'a pas été trouvée.");
        }
        return $this->redirectToRoute('rh_dashboard');
    }

    #[Route('/rh/edit-opportunity/{id}', name: 'rh_edit_opportunity', methods: ['GET', 'POST'])]
    public function editOpportunity(int $id, Request $request, OpportunityRepository $opportunityRepository, EntityManagerInterface $em): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if (!$opportunity) {
            $this->addFlash('error', "L'opportunité n'a pas été trouvée.");
            return $this->redirectToRoute('rh_dashboard');
        }
        if ($request->isMethod('POST')) {
            $opportunity->setName($request->request->get('opportunityName'));
            $opportunity->setCompany($request->request->get('companyName'));
            $opportunity->setContact($request->request->get('contactPerson'));
            $opportunity->setValue((int)$request->request->get('opportunityValue'));
            $opportunity->setProbability((int)$request->request->get('probability'));
            $opportunity->setStatus($request->request->get('status'));
            $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate')));
            $opportunity->setDescription($request->request->get('description'));
            $em->flush();
            $this->addFlash('success', 'Opportunité modifiée avec succès !');
            return $this->redirectToRoute('rh_dashboard');
        }
        return $this->render('edit_opportunity.html.twig', [
            'opportunity' => $opportunity
        ]);
    }

    #[Route('/inform-admin', name: 'inform_admin', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function informAdmin(MailerInterface $mailer, UserRepository $userRepository, Environment $twig): Response
    {
        // Find the admin user(s)
        $admins = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->getQuery()->getResult();
        $adminEmails = array_map(fn($admin) => $admin->getEmail(), $admins);
        if (empty($adminEmails)) {
            $this->addFlash('error', 'No admin found to notify.');
            return $this->redirectToRoute('rh_dashboard');
        }
        $rh = $this->getUser();
        $html = $twig->render('emails/rh_inform_notification.html.twig', [
            'rh' => $rh
        ]);
        $email = (new Email())
            ->from('noreply@symapp.com')
            ->to(...$adminEmails)
            ->subject('RH Modification Notification')
            ->html($html);
        $mailer->send($email);
        $this->addFlash('success', 'Admin has been notified by email.');
        return $this->redirectToRoute('rh_dashboard');
    }
} 