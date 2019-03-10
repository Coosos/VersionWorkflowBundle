<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Doctrine\ORM\Event\PreFlushEventArgs;

/**
 * Class PreFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreFlushListener
{
    /**
     * @param PreFlushEventArgs $args
     * @throws \Doctrine\ORM\ORMException
     */
    public function preFlush(PreFlushEventArgs $args)
    {
//        $inserts = $args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
//        foreach ($inserts as $insert) {
//            if (!$insert instanceof VersionWorkflow) {
//                continue;
//            }
//
//            if ($insert->getObjectDeserialized()) {
//                $args->getEntityManager()->persist($insert->getObjectDeserialized());
//            }
//        }
    }
}
