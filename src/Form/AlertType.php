<?php
// src/Form/AlertType.php

namespace App\Form;

use App\Entity\Alert;
use App\Entity\FireDetection;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn(User $u) => sprintf('%s <%s>', $u->getName(), $u->getEmail()),
                'label'        => 'Destinataire',
                'placeholder'  => '— Choisir un utilisateur —',
                'attr'         => ['class' => 'form-select'],
            ])
            ->add('detection', EntityType::class, [
                'class'        => FireDetection::class,
                'choice_label' => fn(FireDetection $d) => sprintf(
                    '#%d — %s FRP %.1f MW (%s)',
                    $d->getId(),
                    $d->getSource(),
                    $d->getFrp() ?? 0,
                    $d->getAcqDatetime()->format('d/m H:i')
                ),
                'query_builder' => fn(\Doctrine\ORM\EntityRepository $er) =>
                    $er->createQueryBuilder('f')
                        ->where('f.frp > 15')
                        ->andWhere('f.acqDatetime >= :since')
                        ->setParameter('since', new \DateTimeImmutable('-48 hours'))
                        ->orderBy('f.frp', 'DESC')
                        ->setMaxResults(50),
                'label'    => 'Détection associée (optionnel)',
                'required' => false,
                'placeholder' => '— Aucune détection liée —',
                'attr'     => ['class' => 'form-select'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message d\'alerte',
                'attr'  => [
                    'rows'        => 5,
                    'class'       => 'form-control',
                    'placeholder' => 'Décrivez la situation : zone touchée, intensité, recommandations...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Alert::class]);
    }
}
