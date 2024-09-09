<?php

namespace App\Form;

use App\Entity\Region;
use App\Entity\Department;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;

class SearchFormType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher une annonce...',
                    'class' => 'form-control'
                ]
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'name',
                'label' => 'Région',
                'required' => false,
                'placeholder' => 'Toutes les régions',
                'attr' => ['class' => 'form-control']
            ])
            ->add('search', SubmitType::class, [
                'label' => 'Rechercher',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $this->addDepartmentField($form);
        });

        $builder->get('region')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm()->getParent();
            $this->addDepartmentField($form, $event->getForm()->getData());
        });
    }

    private function addDepartmentField(FormInterface $form, ?Region $region = null): void
    {
        $departments = null === $region
            ? []
            : $this->entityManager->getRepository(Department::class)->findBy(['region' => $region]);

        $form->add('department', EntityType::class, [
            'class' => Department::class,
            'placeholder' => 'Tous les départements',
            'choices' => $departments,
            'choice_label' => 'name',
            'required' => false,
            'label' => 'Département',
            'attr' => ['class' => 'form-control'],
            'disabled' => null === $region,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
