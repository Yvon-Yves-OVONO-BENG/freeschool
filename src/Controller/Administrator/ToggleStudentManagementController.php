<?php

namespace App\Controller\Administrator;

use App\Entity\ConstantsClass;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/administrators')]
class ToggleStudentManagementController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
    ) {
    }

    #[Route('/toggle-student-management/{id<\d+>}', name: 'toggle_administrator_student_management', methods: ['POST'])]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        if (!$this->isGranted(ConstantsClass::ROLE_HEADMASTER)) {
            return $this->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $administrator = $this->userRepository->find($id);

        if (!$administrator || !in_array(ConstantsClass::ROLE_ADMIN, $administrator->getRoles(), true)) {
            return $this->json(['success' => false, 'message' => 'Administrateur introuvable.'], 404);
        }

        if (!$this->isCsrfTokenValid(
            'toggle_student_management_'.$administrator->getId(),
            (string) $request->request->get('_token')
        )) {
            return $this->json(['success' => false, 'message' => 'Jeton de sécurité invalide.'], 419);
        }

        $blocked = filter_var(
            $request->request->get('blocked'),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($blocked === null) {
            return $this->json(['success' => false, 'message' => 'État invalide.'], 422);
        }

        $administrator->setStudentManagementBlocked($blocked);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'blocked' => $administrator->isStudentManagementBlocked(),
            'message' => $administrator->isStudentManagementBlocked()
                ? 'Ajout et modification des élèves bloqués.'
                : 'Ajout et modification des élèves autorisés.',
        ]);
    }
}
