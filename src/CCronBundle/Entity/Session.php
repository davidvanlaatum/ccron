<?php

namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sessions")
 */
class Session {
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=128)
     */
    protected $sess_id;

    /** @ORM\Column(type="blob",nullable=false) */
    protected $sess_data;

    /** @ORM\Column(type="integer",nullable=false) */
    protected $sess_time;

    /** @ORM\Column(type="integer",nullable=false) */
    protected $sess_lifetime;
}
