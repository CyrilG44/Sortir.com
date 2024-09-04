<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use phpDocumentor\Reflection\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => "Nom",
                'required' => true,
            ])
            ->add('starting_date', null, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'invalid_message' => 'Veuillez saisir une date valide',
                'label' => "Date",
                'required' => true,
            ])
            ->add('duration_hours', null,  [
                'label' => "Durée (en heure)",
                'required' => false,
            ])
            ->add('registration_limit_date', null, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'invalid_message' => 'Veuillez saisir une date valide',
                'label' => "Date limite d'inscription",
                'required' => true,
            ])
            ->add('registration_max_nb', null,  [
                'label' => "Nombre d'inscriptions maximum",
                'required' => true,
            ])
            ->add('description', null,  [
                'label' => "Description",
                'required' => false,
            ])
            //->add('photo_url', null,  [
                //'label' => "URL de la photo",
                //'required' => false,
            //])
            ->add('place', null, [
                'class' => Place::class,
                'choice_label' => 'name',
                'label' => 'Lieu',
                'required' => true,
            ])
        ;
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
