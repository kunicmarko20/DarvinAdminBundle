<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\View\Index\Body;

/**
 * Index view body row item
 */
class BodyRowItem
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    private $attr;

    /**
     * @param string $content Content
     * @param array  $attr    HTML attributes
     */
    public function __construct($content = null, array $attr = [])
    {
        $this->content = $content;
        $this->attr = $attr;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getAttr()
    {
        return $this->attr;
    }
}
