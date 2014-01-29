<?php

/**
 * ITEA Office copyright message placeholder
 *
 * @category    Calendar
 * @package     View
 * @subpackage  Helper
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2014 ITEA Office (http://itea3.org)
 */
namespace Calendar\View\Helper;

use Zend\View\Helper\AbstractHelper;

use Calendar\Entity;

/**
 * Create a link to an project
 *
 * @category    Calendar
 * @package     View
 * @subpackage  Helper
 */
class DocumentLink extends AbstractHelper
{
    /**
     * @param Entity\Document $document
     * @param string          $action
     * @param string          $show
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function __invoke(
        Entity\Document $document = null,
        $action = 'view',
        $show = 'text'

    )
    {
        $translate = $this->view->plugin('translate');
        $url       = $this->view->plugin('url');
        $serverUrl = $this->view->plugin('serverUrl');

        $params = array(
            'entity' => 'document'
        );

        switch ($action) {
            case 'document-community':
                $router = 'community/calendar/document/document';
                $text   = sprintf($translate("txt-view-calendar-document-%s"), $document->getDocument());
                break;
            case 'download':
                $params['filename'] = $document->parseFileName();
                $params['ext']      = $document->getContentType()->getExtension();
                $router             = 'community/calendar/document/download';
                $text               = sprintf($translate("txt-download-calendar-document-%s"), $document->getDocument());
                break;

            default:
                throw new \InvalidArgumentException(sprintf("%s is an incorrect action for %s", $action, __CLASS__));
        }

        $params['id'] = $document->getId();
        $classes      = array();
        $linkContent  = array();

        switch ($show) {
            case 'icon':
                if ($action === 'edit') {
                    $linkContent[] = '<span class="glyphicon glyphicon-edit"></span>';
                } elseif ($action === 'download') {
                    $linkContent[] = '<span class="glyphicon glyphicon-download"></span>';
                } else {
                    $linkContent[] = '<span class="glyphicon glyphicon-info-sign"></span>';
                }
                break;
            case 'button':
                $linkContent[] = '<span class="glyphicon glyphicon-info"></span> ' . $text;
                $classes[]     = "btn btn-primary";
                break;
            case 'name':
                $linkContent[] = $document->parseFileName();
                break;
            default:
                $linkContent[] = $document->getDocument();
                break;
        }

        $uri = '<a href="%s" title="%s" class="%s">%s</a>';

        return sprintf(
            $uri,
            $serverUrl->__invoke() . $url($router, $params),
            $text,
            implode($classes),
            implode($linkContent)
        );
    }
}
