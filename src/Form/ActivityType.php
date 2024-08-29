<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Place;
use App\Entity\State;
use App\Entity\User;
use phpDocumentor\Reflection\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => "Nom de la sortie",
                'required' => true,
            ])
            ->add('starting_date', null, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'Veuillez saisir une date valide',
                'label' => "Date de la sortie",
                'required' => true,
            ])
            ->add('duration_hours', null,  [
                'label' => "Durée de la sortie (en heure)",
                'required' => false,
            ])
            ->add('registration_limit_date', null, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'Veuillez saisir une date valide',
                'label' => "Date limite d'inscription",
                'required' => true,
            ])
            ->add('registration_max_nb', null,  [
                'label' => "Nombre d'inscription maximum",
                'required' => true,
            ])
            ->add('description', null,  [
                'label' => "Description de la sortie",
                'required' => false,
            ])
            ->add('photo_url', null,  [
                'label' => "Url de la photo",
                'required' => false,
            ])
            ->add('organizer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => "Organisateur",
                'required' => true,
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'name',
                'label' => "Lieu de la sortie",
                'required' => true,
            ])

            ->add('cancelReason', null,[
                'required' => true
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
