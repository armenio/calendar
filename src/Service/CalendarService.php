<?php
/**
 * ITEA Office copyright message placeholder
 *
 * @category  Calendar
 * @package   Service
 * @author    Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright Copyright (c) 2004-2014 ITEA Office (https://itea3.org)
 */
namespace Calendar\Service;

use Calendar\Entity;
use Calendar\Entity\Calendar;
use Calendar\Entity\Contact as CalendarContact;
use Contact\Entity\Contact;
use Project\Entity\Project;

/**
 *
 */
class CalendarService extends ServiceAbstract
{
    /**
     * Constant to determine which affiliations must be taken from the database
     */
    const WHICH_UPCOMING = 'upcoming';
    const WHICH_UPDATED = 'updated';
    const WHICH_PAST = 'past';
    const WHICH_REVIEWS = 'project-reviews';
    const WHICH_ON_HOMEPAGE = 'on-homepage';


    /**
     * @param $id
     *
     * @return null|Calendar
     */
    public function findCalendarById($id)
    {
        return $this->getEntityManager()->getRepository(Calendar::class)->find($id);
    }

    /**
     * @param $docRef
     *
     * @return null|Entity\Calendar
     */
    public function findCalendarByDocRef($docRef)
    {
        return $this->getEntityManager()->getRepository(Entity\Calendar::class)->findOneBy([
            'docRef' => $docRef,
        ]);
    }


    /**
     * @param Calendar $calendar
     * @param Contact  $contact
     *
     * @return bool
     */
    public function calendarHasContact(Calendar $calendar, Contact $contact)
    {
        $calendarContact = $this->getEntityManager()->getRepository(CalendarContact::class)->findOneBy([
            'calendar' => $calendar,
            'contact'  => $contact,
        ]);

        return !is_null($calendarContact);
    }

    /**
     * @param array    $data
     * @param Calendar $calendar
     *
     * array (size=5)
     * 'type' => string '2' (length=1)
     * 'added' => string '20388' (length=5)
     * 'removed' => string '' (length=0)
     * 'sql' => string '' (length=0)
     *
     *
     */
    public function updateCalendarContacts(Calendar $calendar, array $data)
    {
        //Update the contacts
        if (!empty($data['added'])) {
            foreach (explode(',', $data['added']) as $contactId) {
                $contact = $this->getContactService()->findEntityById(Contact::class, $contactId);

                if (!is_null($contact) && !$this->calendarHasContact($calendar, $contact)) {
                    $calendarContact = new CalendarContact();
                    $calendarContact->setContact($contact);
                    $calendarContact->setCalendar($calendar);

                    /**
                     * Add every new user as attendee
                     *
                     * @var $role Entity\ContactRole
                     */
                    $role = $this->findEntityById(Entity\ContactRole::class, Entity\ContactRole::ROLE_ATTENDEE);
                    $calendarContact->setRole($role);

                    /**
                     * Add every new user as attendee
                     *
                     * @var $status Entity\ContactStatus
                     */
                    $status = $this->findEntityById(
                        Entity\ContactStatus::class,
                        Entity\ContactStatus::STATUS_TENTATIVE
                    );
                    $calendarContact->setStatus($status);

                    $this->newEntity($calendarContact);
                }
            }
        }

        //Update the contacts
        if (!empty($data['removed'])) {
            foreach (explode(',', $data['removed']) as $contactId) {
                foreach ($calendar->getCalendarContact() as $calendarContact) {
                    if ($calendarContact->getContact()->getId() === (int)$contactId) {
                        $this->removeEntity($calendarContact);
                    }
                }
            }
        }
    }

    /**
     * @param  string  $which
     * @param  Contact $contact
     *
     * @return CalendarContact[]
     */
    public function findCalendarContactByContact(
        $which = self::WHICH_UPCOMING,
        Contact $contact = null
    ) {
        return $this->getEntityManager()->getRepository(CalendarContact::class)
            ->findCalendarContactByContact($which, $contact);
    }

    /**
     * @param Contact  $contact
     * @param Calendar $calendar
     *
     * @return CalendarContact
     */
    public function findCalendarContactByContactAndCalendar(
        Contact $contact,
        Calendar $calendar
    ) {
        return $this->getEntityManager()->getRepository(CalendarContact::class)
            ->findCalendarContactByContactAndCalendar($contact, $calendar);
    }

    /**
     * @param Calendar $calendar
     * @param int      $status
     *
     * @return CalendarContact[]
     */
    public function findCalendarContactsByCalendar(Calendar $calendar, $status = CalendarContact::STATUS_ALL)
    {
        return $this->getEntityManager()->getRepository(CalendarContact::class)
            ->findCalendarContactsByCalendar($calendar, $status);
    }

    /**
     * This function will return a boolean value to see if a contact can view the calendar
     *
     * @param Calendar $calendar
     * @param Contact  $contact
     *
     * @return bool
     */
    public function canViewCalendar(Calendar $calendar, Contact $contact = null)
    {
        return $this->getEntityManager()->getRepository(Entity\Calendar::class)->canViewCalendar($calendar, $contact);
    }

    /**
     * @param string  $which
     * @param Contact $contact
     * @param integer $year
     * @param integer $type
     *
     * @return \Doctrine\ORM\Query
     */
    public function findCalendarItems(
        $which = self::WHICH_UPCOMING,
        Contact $contact = null,
        $year = null,
        $type = null
    ) {
        return $this->getEntityManager()->getRepository(Entity\Calendar::class)
            ->findCalendarItems($which, true, $contact, $year, $type);
    }

    /**
     * @param bool    $onlyFinal
     * @param Project $project
     *
     * @return Calendar[]
     */
    public function findCalendarByProject(Project $project, $onlyFinal = true)
    {
        $calendar = [];
        /**
         * Add the calendar items from the project
         */
        foreach ($project->getProjectCalendar() as $calendarItem) {
            if (!$onlyFinal
                || $calendarItem->getCalendar()->getFinal() === Calendar::FINAL_FINAL
            ) {
                $calendar[$calendarItem->getCalendar()->getId()]
                    = $calendarItem->getCalendar();
            }
        }
        foreach ($project->getCall()->getCalendar() as $calendarItem) {
            if (!$onlyFinal
                || $calendarItem->getFinal() === Calendar::FINAL_FINAL
            ) {
                if ($calendarItem->getDateEnd() > new \DateTime()) {
                    $calendar[$calendarItem->getId()] = $calendarItem;
                }
            }
        }

        return $calendar;
    }

    /**
     * return the review-meeting corresponding to a calendar item
     *
     * @param Project $project
     *
     * @return Calendar|null
     */
    public function findLatestProjectCalendar(Project $project)
    {
        return $this->getEntityManager()->getRepository(Entity\Calendar::class)->findLatestProjectCalendar($project);
    }

    /**
     * Return the news review meeting
     *
     * @param Project   $project
     * @param \DateTime $datetime
     *
     * @return Calendar|null
     */
    public function findNextProjectCalendar(
        Project $project,
        \DateTime $datetime
    ) {
        return $this->getEntityManager()->getRepository(Calendar::class)->findNextProjectCalendar($project, $datetime);
    }

    /**
     * Return the lastest review meeting
     *
     * @param Project   $project
     * @param \DateTime $datetime
     *
     * @return Calendar|null
     */
    public function findPreviousProjectCalendar(
        Project $project,
        \DateTime $datetime
    ) {
        return $this->getEntityManager()->getRepository(Calendar::class)
            ->findPreviousProjectCalendar($project, $datetime);
    }

    /**
     * @param CalendarContact $calendarContact
     * @param                 $status
     */
    public function updateContactStatus(
        CalendarContact $calendarContact,
        $status
    ) {
        $calendarContact->setStatus($this->getEntityManager()->getReference(Entity\ContactStatus::class, $status));
        $this->updateEntity($calendarContact);
    }

    /**
     * @param Calendar $calendar
     *
     * @return CalendarContact[]
     */
    public function findGeneralCalendarContactByCalendar(Calendar $calendar)
    {
        return $this->getEntityManager()->getRepository(CalendarContact::class)
            ->findGeneralCalendarContactByCalendar($calendar);
    }

    /**
     * Return an array of all which-values
     *
     * @return string[]
     */
    public function getWhichValues()
    {
        return [
            self::WHICH_UPCOMING,
            self::WHICH_UPDATED,
            self::WHICH_PAST,
            self::WHICH_REVIEWS,
        ];
    }
}
