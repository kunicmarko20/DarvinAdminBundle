<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2016, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Search;

use Darvin\AdminBundle\Metadata\Metadata;
use Darvin\AdminBundle\Metadata\MetadataManager;
use Darvin\AdminBundle\Security\Permissions\Permission;
use Darvin\ContentBundle\Filterer\FiltererException;
use Darvin\ContentBundle\Filterer\FiltererInterface;
use Darvin\ContentBundle\Translatable\TranslationJoinerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Searcher
 */
class Searcher
{
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Darvin\ContentBundle\Filterer\FiltererInterface
     */
    private $filterer;

    /**
     * @var \Darvin\AdminBundle\Metadata\MetadataManager
     */
    private $metadataManager;

    /**
     * @var \Darvin\ContentBundle\Translatable\TranslationJoinerInterface
     */
    private $translationJoiner;

    /**
     * @var \Darvin\AdminBundle\Metadata\Metadata[]
     */
    private $searchableEntitiesMeta;

    /**
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker Authorization checker
     * @param \Doctrine\ORM\EntityManager                                                  $em                   Entity manager
     * @param \Darvin\ContentBundle\Filterer\FiltererInterface                             $filterer             Filterer
     * @param \Darvin\AdminBundle\Metadata\MetadataManager                                 $metadataManager      Metadata manager
     * @param \Darvin\ContentBundle\Translatable\TranslationJoinerInterface                $translationJoiner    Translation joiner
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManager $em,
        FiltererInterface $filterer,
        MetadataManager $metadataManager,
        TranslationJoinerInterface $translationJoiner
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
        $this->filterer = $filterer;
        $this->metadataManager = $metadataManager;
        $this->translationJoiner = $translationJoiner;

        $this->searchableEntitiesMeta = null;
    }

    /**
     * @param string $entityName Entity name
     * @param string $query      Search query
     *
     * @return object[]
     * @throws \Darvin\AdminBundle\Search\SearchException
     */
    public function search($entityName, $query)
    {
        $meta = $this->getSearchableEntityMeta($entityName);

        $qb = $this->em->getRepository($meta->getEntityClass())->createQueryBuilder('o');

        if ($this->translationJoiner->isTranslatable($meta->getEntityClass())) {
            $this->translationJoiner->joinTranslation($qb, true, null, null, true);
        }

        $searchableFields = $this->getSearchableFields($meta);

        try {
            $this->filterer->filter($qb, array_fill_keys($searchableFields, $query), [
                'non_strict_comparison_fields' => $searchableFields,
            ], false);
        } catch (FiltererException $ex) {
            throw new SearchException(
                sprintf('Unable to search for "%s" entities: "%s".', $meta->getEntityClass(), $ex->getMessage())
            );
        }
        try {
            return $qb->getQuery()->getResult();
        } catch (QueryException $ex) {
            throw new SearchException(
                sprintf('Unable to search for "%s" entities: "%s".', $meta->getEntityClass(), $ex->getMessage())
            );
        }
    }

    /**
     * @param string $entityName Entity name
     *
     * @return \Darvin\AdminBundle\Metadata\Metadata
     * @throws \Darvin\AdminBundle\Search\SearchException
     */
    public function getSearchableEntityMeta($entityName)
    {
        $allMeta = $this->getSearchableEntitiesMeta();

        if (!isset($allMeta[$entityName])) {
            throw new SearchException(sprintf('Entity "%s" is not searchable.', $entityName));
        }

        return $allMeta[$entityName];
    }

    /**
     * @return string[]
     */
    public function getSearchableEntityNames()
    {
        return array_keys($this->getSearchableEntitiesMeta());
    }

    /**
     * @param string $entityName Entity name
     *
     * @return bool
     */
    public function isSearchable($entityName)
    {
        $allMeta = $this->getSearchableEntitiesMeta();

        return isset($allMeta[$entityName]);
    }

    /**
     * @return \Darvin\AdminBundle\Metadata\Metadata[]
     */
    private function getSearchableEntitiesMeta()
    {
        if (null === $this->searchableEntitiesMeta) {
            $this->searchableEntitiesMeta = [];

            foreach ($this->metadataManager->getAllMetadata() as $meta) {
                if (!$this->authorizationChecker->isGranted(Permission::EDIT, $meta->getEntityClass())
                    && !$this->authorizationChecker->isGranted(Permission::VIEW)
                ) {
                    continue;
                }

                $searchableFields = $this->getSearchableFields($meta);

                if (!empty($searchableFields)) {
                    $this->searchableEntitiesMeta[$meta->getEntityName()] = $meta;
                }
            }
        }

        return $this->searchableEntitiesMeta;
    }

    /**
     * @param \Darvin\AdminBundle\Metadata\Metadata $meta Metadata
     *
     * @return string[]
     */
    private function getSearchableFields(Metadata $meta)
    {
        $config = $meta->getConfiguration();

        return $config['searchable_fields'];
    }
}
