<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class home extends AbstractController
{
    #[Route('/', name: 'home')]


    public function index(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}
