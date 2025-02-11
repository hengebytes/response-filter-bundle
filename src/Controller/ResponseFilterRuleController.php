<?php

declare(strict_types=1);

namespace Hengebytes\ResponseFilterBundle\Controller;

use Hengebytes\ResponseFilterBundle\Entity\ResponseFilterRule;
use Hengebytes\ResponseFilterBundle\Form\ResponseFilterRuleType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/response-filter-rule', name: 'response_filter_rule_')]
class ResponseFilterRuleController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('@HBResponseFilter/layout.html.twig', [
            'entities' => $this->entityManager->getRepository(ResponseFilterRule::class)->findAll(),
            'action' => 'response_filter_index',
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST', 'GET'])]
    public function create(Request $request): Response
    {
        return $this->handleCreateUpdate(new ResponseFilterRule(), $request);
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST', 'GET'])]
    public function update(int $id, Request $request): Response
    {
        $entity = $this->entityManager->getRepository(ResponseFilterRule::class)->find($id);
        if (!$entity) {
            return $this->redirectToRoute('response_filter_rule_index');
        }

        return $this->handleCreateUpdate($entity, $request, 'update');
    }

    #[Route('/copy/{id}', name: 'copy', methods: ['POST', 'GET'])]
    public function copy(int $id, Request $request): Response
    {
        $entity = $this->entityManager->getRepository(ResponseFilterRule::class)->find($id);
        if (!$entity) {
            return $this->redirectToRoute('response_filter_rule_index');
        }
        $entity = clone $entity;

        return $this->handleCreateUpdate($entity, $request);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['GET'])]
    public function delete(int $id): Response
    {
        $entity = $this->entityManager->getRepository(ResponseFilterRule::class)->find($id);
        if ($entity) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('response_filter_rule_index');
    }


    public function getResponseFilterForm(ResponseFilterRule $entity): FormInterface|Form
    {
        $form = $this->createForm(ResponseFilterRuleType::class, $entity);
        try {
            $form = $form
                ->add('submit', SubmitType::class, [
                    'label' => 'Save',
                    'attr' => [
                        'class' => 'btn-success',
                    ],
                ])
                ->add('cancel', SubmitType::class, [
                    'label' => 'Cancel',
                    'attr' => [
                        'class' => ' btn-danger',
                        'formnovalidate' => true,
                    ],
                ]);
        } catch (Exception $e) {
            // ignore
        }

        return $form;
    }

    private function handleCreateUpdate(
        ResponseFilterRule $entity, Request $request, string $action = 'create'
    ): Response|RedirectResponse {
        $form = $this->getResponseFilterForm($entity);

        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirectToRoute('response_filter_rule_index');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirectToRoute('response_filter_rule_index');
        }

        return $this->render('@HBResponseFilter/layout.html.twig', [
            'action' => 'response_filter_' . $action,
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }
}
