<?php

namespace Gitonomy\Bundle\FrontendBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('username', 'text')
            ->add('fullname', 'text')
            ->add('defaultEmail', 'useremail')
            ->add('timezone', 'timezone')
            ->add('password', 'repeated',array('type' => 'password'))
        ;
    }

    public function getName()
    {
        return 'registration';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'validation_groups' => array('registration')
        );
    }
}
