<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\View\Widget\Widget;

use Darvin\AdminBundle\Metadata\IdentifierAccessor;
use Darvin\AdminBundle\Security\Permissions\Permission;
use Darvin\AdminBundle\View\Widget\WidgetException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Child links view widget
 */
class ChildLinksWidget extends AbstractWidget
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Darvin\AdminBundle\Metadata\IdentifierAccessor
     */
    private $identifierAccessor;

    /**
     * @param \Doctrine\ORM\EntityManager $em Entity manager
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param \Darvin\AdminBundle\Metadata\IdentifierAccessor $identifierAccessor Identifier accessor
     */
    public function setIdentifierAccessor(IdentifierAccessor $identifierAccessor)
    {
        $this->identifierAccessor = $identifierAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContent($entity, array $options, $property)
    {
        $childClass = $options['child_entity'];

        $viewPermissionGranted = $this->isGranted(Permission::VIEW, $childClass);
        $createDeletePermissionGranted = $this->isGranted(Permission::CREATE_DELETE, $childClass);

        if (!$viewPermissionGranted && !$createDeletePermissionGranted) {
            return null;
        }

        $parentMeta = $this->metadataManager->getMetadata($entity);

        if (!$parentMeta->hasChild($childClass)) {
            throw new WidgetException(
                sprintf('Entity "%s" is not child of entity "%s".', $childClass, $parentMeta->getEntityClass())
            );
        }

        $childMeta = $parentMeta->getChild($childClass);
        $association = $childMeta->getAssociation();

        $parentId = $this->identifierAccessor->getValue($entity);

        $childrenCount = (int) $this->em->getRepository($childClass)->createQueryBuilder('o')
            ->select('COUNT(o)')
            ->where(sprintf('o.%s = :%1$s', $association))
            ->setParameter($association, $parentId)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render($options, [
            'association_param'  => $childMeta->getAssociationParameterName(),
            'child_class'        => $childClass,
            'children_count'     => $childrenCount,
            'index_link'         => $viewPermissionGranted,
            'new_link'           => $createDeletePermissionGranted,
            'parent_id'          => $parentId,
            'translation_prefix' => $childMeta->getMetadata()->getBaseTranslationPrefix(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired('child_entity')
            ->setAllowedTypes('child_entity', 'string');
    }
}