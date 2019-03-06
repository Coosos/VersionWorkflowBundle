<?php

namespace Coosos\VersionWorkflowBundle\Entity;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class VersionWorkflow
 *
 * @package Coosos\VersionWorkflowBundle\Entity
 * @author  Remy Lescallier <lescallier1@gmail.com>
 * @ORM\Entity(repositoryClass="Coosos\VersionWorkflowBundle\Repository\VersionWorkflowRepository")
 */
class VersionWorkflow extends VersionWorkflowModel
{
    /**
     * @var VersionWorkflowModel|null
     * @ORM\ManyToOne(targetEntity="Coosos\VersionWorkflowBundle\Entity\VersionWorkflow")
     */
    protected $inherit;

    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $workflowName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $modelName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $marking;

    /**
     * @var string|null
     * @ORM\Column(type="text")
     */
    protected $objectSerialized;
}
