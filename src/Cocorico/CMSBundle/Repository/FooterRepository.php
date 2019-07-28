<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocorico\CMSBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

class FooterRepository extends EntityRepository
{
    /**
     * @param string $locale
     * @return array|null
     */
    public function findByHash($locale)
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->addSelect("ft")
            ->leftJoin('f.translations', 'ft')
            ->where('ft.locale = :locale')
            ->andWhere('f.published = :published')
            ->setParameter('locale', $locale)
            ->setParameter('published', true)
            ->orderBy('ft.title');
        try {
            $query = $queryBuilder->getQuery();

            return $query->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

}
