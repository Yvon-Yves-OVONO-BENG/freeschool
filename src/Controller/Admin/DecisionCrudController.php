<?php

namespace App\Controller\Admin;

use App\Entity\Decision;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class DecisionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Decision::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
