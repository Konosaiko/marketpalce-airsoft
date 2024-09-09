<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Listing;
use App\Entity\Region;
use App\Entity\Department;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Doctrine\ORM\EntityManagerInterface;

class ListingFormType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre de l\'annonce'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('price', MoneyType::class, ['label' => 'Prix', 'currency' => 'EUR'])
            ->add('state', TextType::class, ['label' => 'État'])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
            ])
            ->add('region', EntityType::class, [
                'class' => Region::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une région',
                'mapped' => false,
                'required' => true,
                'label' => 'Région',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $listing = $event->getData();

            $region = $listing->getRegion() ? $this->entityManager->getRepository(Region::class)->findOneBy(['name' => $listing->getRegion()]) : null;
            $this->addDepartmentField($form, $region);
        });

        $builder->get('region')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $this->addDepartmentField($form->getParent(), $form->getData());
        });

        $builder->add('photoFiles', FileType::class, [
            'label' => 'Photos',
            'multiple' => true,
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new All([
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide',
                    ])
                ])
            ],
        ]);
    }

    private function addDepartmentField(FormInterface $form, ?Region $region = null): void
    {
        $departments = null === $region
            ? []
            : $this->entityManager->getRepository(Department::class)->findBy(['region' => $region]);

        $form->add('department', EntityType::class, [
            'class' => Department::class,
            'placeholder' => 'Sélectionnez un département',
            'choices' => $departments,
            'choice_label' => 'name',
            'mapped' => false,
            'required' => true,
            'label' => 'Département',
            'disabled' => null === $region,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Listing::class,
        ]);
    }
}