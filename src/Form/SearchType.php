<?php
namespace App\Form;

use App\Data\SearchProjet;
use App\Entity\Projet;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sujet',TextType::class,[
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Sujet'
                ]
            ])
            ->add('nomEtudiant',TextType::class,[
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'nom de l\'étudiant(e)'
                ]
            ])
            ->add('nomTuteur',TextType::class,[
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'nom de l\'enseigant(e)'
                ]
            ])
            ->add('date',TextType::class,[
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'année'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SearchProjet::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }
    public function getBlockPrefix()
    {
        return '';
    }
}