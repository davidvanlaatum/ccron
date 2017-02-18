<?php
namespace CCronBundle\Controller;

use CCronBundle\Form\CronType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class JobForm extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', TextType::class)
            ->add('cronSchedule', CronType::class, ['label' => 'job.schedule.title'])
            ->add('command', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'job.save'))
            ->add('delete', SubmitType::class, array('label' => 'job.delete'));
    }
}
