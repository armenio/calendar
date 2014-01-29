<?php
/**
 * ITEA Office copyright message placeholder
 *
 * @category    Calendar
 * @package     Controller
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2014 ITEA Office (http://itea3.org)
 */
namespace Calendar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Paginator\Paginator;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

use Calendar\Service\FormServiceAwareInterface;
use Calendar\Service\CalendarService;
use Calendar\Service\FormService;

/**
 *
 */
class CalendarCommunityController extends AbstractActionController implements
    FormServiceAwareInterface,
    ServiceLocatorAwareInterface
{

    /**
     * @var CalendarService;
     */
    protected $calendarService;
    /**
     * @var FormService
     */
    protected $formService;
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Trigger to switch layout
     *
     * @param $layout
     *
     * @return void
     */
    public function layout($layout)
    {
        if (false === $layout) {
            $this->getEvent()->getViewModel()->setTemplate('layout/nolayout');
        } else {
            $this->getEvent()->getViewModel()->setTemplate($layout);
        }
    }

    /**
     * @return ViewModel
     */
    public function overviewAction()
    {
        $which = $this->getEvent()->getRouteMatch()->getParam('which', 'upcoming');
        $page  = $this->getEvent()->getRouteMatch()->getParam('page', 1);

        $calendarItems = $this->getCalendarService()->findCalendarItems($which);
        $paginator     = new Paginator(new PaginatorAdapter(new ORMPaginator($calendarItems)));
        $paginator->setDefaultItemCountPerPage(($page === 'all') ? PHP_INT_MAX : 15);
        $paginator->setCurrentPageNumber($page);
        $paginator->setPageRange(ceil($paginator->getTotalItemCount() / $paginator->getDefaultItemCountPerPage()));

        $whichValues = $this->getCalendarService()->getWhichValues();

        return new ViewModel(array(
            'which'       => $which,
            'paginator'   => $paginator,
            'whichValues' => $whichValues
        ));
    }

    /**
     * Special action which produces an HTML version of the review calendar
     *
     * @return ViewModel
     */
    public function reviewCalendarAction()
    {
        $calendarItems = $this->getCalendarService()->findCalendarItems(CalendarService::WHICH_REVIEWS)->getResult();

        return new ViewModel(array(
            'calendarItems' => $calendarItems,
        ));
    }


    /**
     * @return ViewModel
     */
    public function calendarAction()
    {
        $calendar = $this->getCalendarService()->findEntityById('calendar',
            $this->getEvent()->getRouteMatch()->getParam('id'));

        return new ViewModel(array('calendar' => $calendar));
    }


    /**
     * @return \Calendar\Service\FormService
     */
    public function getFormService()
    {
        return $this->formService;
    }

    /**
     * @param $formService
     *
     * @return CalendarManagerController
     */
    public function setFormService($formService)
    {
        $this->formService = $formService;

        return $this;
    }

    /**
     * Gateway to the Calendar Service
     *
     * @return CalendarService
     */
    public function getCalendarService()
    {
        return $this->getServiceLocator()->get('calendar_calendar_service');
    }

    /**
     * @param $calendarService
     *
     * @return CalendarManagerController
     */
    public function setCalendarService($calendarService)
    {
        $this->calendarService = $calendarService;

        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return CalendarManagerController|void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }
}
