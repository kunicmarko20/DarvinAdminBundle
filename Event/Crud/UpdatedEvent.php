<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Event\Crud;

use Darvin\UserBundle\Entity\BaseUser;

/**
 * CRUD updated event
 */
class UpdatedEvent extends AbstractEvent
{
    /**
     * @var object
     */
    private $entityBefore;

    /**
     * @var object
     */
    private $entityAfter;

    /**
     * @param \Darvin\UserBundle\Entity\BaseUser $user         User
     * @param object                             $entityBefore Entity before
     * @param object                             $entityAfter  Entity after
     */
    public function __construct(BaseUser $user, $entityBefore, $entityAfter)
    {
        parent::__construct($user);

        $this->entityBefore = $entityBefore;
        $this->entityAfter = $entityAfter;
    }

    /**
     * @return object
     */
    public function getEntityBefore()
    {
        return $this->entityBefore;
    }

    /**
     * @return object
     */
    public function getEntityAfter()
    {
        return $this->entityAfter;
    }
}
