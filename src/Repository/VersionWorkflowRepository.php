<?php

namespace Coosos\VersionWorkflowBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class VersionWorkflowRepository
 *
 * @package Coosos\VersionWorkflowBundle\Repository
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowRepository extends EntityRepository
{
    /**
     * Get list
     *
     * @param string $classPath
     * @param string $workflowName
     * @param string $marking
     * @param bool   $lastOfInstance
     * @return mixed
     */
    public function getList(string $classPath, string $workflowName, $marking, $lastOfInstance = true)
    {
        $queryBuilder = $this->createQueryBuilder('vw');

        $queryBuilder->andWhere($queryBuilder->expr()->eq('vw.modelName', ':entityClass'))
            ->setParameter(':entityClass', $classPath);

        $queryBuilder->andWhere($queryBuilder->expr()->eq('vw.workflowName', ':workflowName'))
            ->setParameter(':workflowName', $workflowName);

        $this->buildQueryConditionByMarking($queryBuilder, $marking);

        if ($lastOfInstance) {
            $queryBuilderNotIn = $this->createQueryBuilder('vw2')
                ->select('IDENTITY(vw2.inherit)')
                ->andWhere('IDENTITY(vw2.inherit) = vw.id')
                ->getQuery();

            $queryBuilder->andWhere($queryBuilder->expr()->notIn('vw.id', $queryBuilderNotIn->getDQL()));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder      $queryBuilder
     * @param array|string|null $marking
     * @param int               $i
     * @param array             $markingValue
     *
     * @return QueryBuilder
     */
    protected function buildQueryConditionByMarking(QueryBuilder $queryBuilder, $marking, &$i = 0, &$markingValue = [])
    {
        if (is_string($marking)) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('vw.marking', ':marking'))
                ->setParameter(':marking', '%' . $marking . '%');
        } elseif (is_array($marking)) {
            $orQuery = $queryBuilder->expr()->orX();
            foreach ($marking as $item) {
                $markingValueId = '_markingvalue_' . $i++;
                $markingValue[$markingValueId] = $item;
                $orQuery->add($queryBuilder->expr()->eq('vw.marking', ':' . $markingValueId));
            }

            $queryBuilder->andWhere($orQuery);
            foreach ($markingValue as $key => $value) {
                $queryBuilder->setParameter(':' . $key, $value);
            }
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull('vw.marking'));
        }

        return $queryBuilder;
    }
}
