<?php

namespace App\Controller\Admin;

use App\Entity\Decision;
use App\Entity\Diploma;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
        )
    {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();
        $url = $this->adminUrlGenerator
                    ->setController(DiplomaCrudController::class)
                    ->generateUrl();

        return $this->redirect($url);
        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Freeschool Bilingue');
    }

    public function configureMenuItems(): iterable
    {
        // yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Accueil', 'fa fa-home');

        /////DATA
        yield MenuItem::section('Data');

        ////DIPLOMA
        yield MenuItem::subMenu('Diploma', 'fas fa-mortar-board')->setSubItems([
            MenuItem::linkToCrud('Add Diploma', 'fas fa-plus', Diploma::class)->setAction(Crud::PAGE_NEW),
            MenuItem::linkToCrud('Display Diploma', 'fas fa-eye', Diploma::class)
        ]);

        //////DECISION
        yield MenuItem::subMenu('Decisions', 'fas fa-gavel')->setSubItems([
            MenuItem::linkToCrud('Add Decision', 'fas fa-plus', Decision::class)->setAction(Crud::PAGE_NEW),
            MenuItem::linkToCrud('Display Decision', 'fas fa-eye', Decision::class)
        ]);
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
