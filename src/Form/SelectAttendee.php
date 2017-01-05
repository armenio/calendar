<?php
/**
 * ITEA Office all rights reserved
 *
 * @category  Calendar
 *
 * @author    Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 */

namespace Calendar\Form;

use Calendar\Entity\Calendar;
use Contact\Service\ContactService;
use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 *
 */
class SelectAttendee extends Form implements InputFilterProviderInterface
{
    /**
     * @param Calendar       $calendar
     * @param ContactService $contactService
     */
    public function __construct(Calendar $calendar, ContactService $contactService)
    {
        parent::__construct();
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('action', '');

        $contacts = [];
        foreach ($contactService->findPossibleContactByCalendar($calendar) as $contact) {
            $contacts[$contact->getId()] = $contact->getDisplayName();
        }

        $this->add(
            [
                'type'    => 'Zend\Form\Element\MultiCheckbox',
                'name'    => 'contact',
                'options' => [
                    'value_options' => $contacts,
                    'label'         => _("txt-contact-name"),
                ],
            ]
        );

        $this->add(
            [
                'type'       => 'Zend\Form\Element\Submit',
                'name'       => 'submit',
                'attributes' => [
                    'class' => "btn btn-primary",
                    'value' => _("txt-update"),
                ],
            ]
        );
        $this->add(
            [
                'type'       => 'Zend\Form\Element\Submit',
                'name'       => 'cancel',
                'attributes' => [
                    'class' => "btn btn-warning",
                    'value' => _("txt-cancel"),
                ],
            ]
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'contact' => [
                'required' => true,
            ],
        ];
    }
}
