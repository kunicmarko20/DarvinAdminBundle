<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Event;

/**
 * Events
 */
final class Events
{
    const PRE_CRUD_CONTROLLER_ACTION = 'darvin_admin.pre_crud_controller_action';
    const PRE_SHOW_VIEW_CREATING     = 'darvin_admin.pre_show_view_creating';
}
