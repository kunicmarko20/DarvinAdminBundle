<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2016, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\View\Widget\Widget;

use Darvin\AdminBundle\Security\Permissions\Permission;
use Darvin\UserBundle\Entity\BaseUser;

/**
 * Switch user view widget
 */
class SwitchUserWidget extends AbstractWidget
{
    /**
     * @param \Darvin\UserBundle\Entity\BaseUser $user     User
     * @param array                              $options  Options
     * @param string                             $property Property name
     *
     * @return string
     */
    protected function createContent($user, array $options, $property)
    {
        if (!$this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return null;
        }
        foreach ($user->getRoles() as $role) {
            if (preg_match('/ADMIN$/', $role)) {
                return $this->render([], [
                    'user' => $user,
                ]);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedEntityClasses()
    {
        return [
            BaseUser::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredPermissions()
    {
        return [
            Permission::VIEW,
        ];
    }
}
