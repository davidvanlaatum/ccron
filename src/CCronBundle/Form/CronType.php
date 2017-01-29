<?php
namespace CCronBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CronType extends AbstractType {

    public function getParent() {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);
    }
}
