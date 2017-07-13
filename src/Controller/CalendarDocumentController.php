<?php
/**
 * ITEA Office all rights reserved
 *
 * @category  Calendar
 *
 * @author    Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 */

declare(strict_types=1);

namespace Calendar\Controller;

use Calendar\Entity\Document;
use Calendar\Entity\DocumentObject;
use Calendar\Form\CreateCalendarDocument;
use Zend\Validator\File\FilesSize;
use Zend\Validator\File\MimeType;
use Zend\View\Model\ViewModel;

/**
 *
 */
class CalendarDocumentController extends CalendarAbstractController
{
    /**
     * @return \Zend\Stdlib\ResponseInterface|ViewModel
     */
    public function downloadAction()
    {
        /**
         * @var Document $document
         */
        $document = $this->getCalendarService()->findEntityById(Document::class, $this->params('id'));
        if (is_null($document) || count($document->getObject()) === 0) {
            return $this->notFoundAction();
        }

        /*
         * Due to the BLOB issue, we treat this as an array and we need to capture the first element
         */
        $object = $document->getObject()->first()->getObject();
        $response = $this->getResponse();
        $response->setContent(stream_get_contents($object));
        $response->getHeaders()->addHeaderLine('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 36000))
            ->addHeaderLine("Cache-Control: max-age=36000, must-revalidate")
            ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $document->parseFileName() . '"')
            ->addHeaderLine("Pragma: public")->addHeaderLine(
                'Content-Type: ' . $document->getContentType()
                    ->getContentType()
            )->addHeaderLine('Content-Length: ' . $document->getSize());

        return $this->response;
    }

    /**
     * @return array|ViewModel
     */
    public function documentAction()
    {
        $document = $this->getCalendarService()->findEntityById(Document::class, $this->params('id'));

        if (is_null($document)) {
            return $this->notFoundAction();
        }

        return new ViewModel(['document' => $document]);
    }

    /**
     * @return array|\Zend\Http\Response|ViewModel
     */
    public function editAction()
    {
        /** @var Document $document */
        $document = $this->getCalendarService()->findEntityById(Document::class, $this->params('id'));
        if (is_null($document)) {
            return $this->notFoundAction();
        }

        $data = array_merge_recursive(
            $this->getRequest()->getPost()->toArray(),
            $this->getRequest()->getFiles()->toArray()
        );
        $form = new CreateCalendarDocument($this->getEntityManager());
        $form->bind($document);
        $form->getInputFilter()->get('file')->setRequired(false);
        $form->setData($data);
        if ($this->getRequest()->isPost() && $form->isValid()) {
            /*
             * @var Document
             */
            $document = $form->getData();
            /*
             * Remove the file if delete is pressed
             */
            if (isset($data['delete'])) {
                $this->flashMessenger()->setNamespace('success')
                    ->addMessage(
                        sprintf(
                            $this->translate("txt-calendar-document-%s-successfully-removed"),
                            $document->parseFileName()
                        )
                    );
                $this->getCalendarService()->removeEntity($document);

                return $this->redirect()
                    ->toRoute(
                        'community/calendar/calendar',
                        ['id' => $document->getCalendar()->getId()],
                        ['fragment' => 'documents']
                    );
            }
            /*
             * Handle when
             */
            if (!isset($data['cancel'])) {
                $file = $form->get('file')->getValue();
                if (!empty($file['name']) && $file['error'] === 0) {

                    /** If no name is given, take the name of the file */
                    if (empty($data['document'])) {
                        $document->setDocument($file['name']);
                    }

                    /*
                     * Update the document
                     */
                    $fileSizeValidator = new FilesSize(PHP_INT_MAX);
                    $fileSizeValidator->isValid($file);
                    $document->setSize($fileSizeValidator->size);

                    $fileTypeValidator = new MimeType();
                    $fileTypeValidator->isValid($file);
                    $document->setContentType($this->getGeneralService()->findContentTypeByContentTypeName($fileTypeValidator->type));

                    /**
                     * Update the object
                     *
                     * @var DocumentObject $documentObject
                     */
                    $documentObject = $document->getObject()->first();
                    $documentObject->setObject(file_get_contents($file['tmp_name']));
                    $this->getCalendarService()->updateEntity($documentObject);
                }
                $this->getCalendarService()->updateEntity($document);
                $this->flashMessenger()->setNamespace('success')
                    ->addMessage(
                        sprintf(
                            $this->translate("txt-calendar-document-%s-successfully-updated"),
                            $document->parseFileName()
                        )
                    );
            }

            return $this->redirect()->toRoute('community/calendar/document/document', ['id' => $document->getId()]);
        }

        return new ViewModel(
            [
                'document' => $document,
                'form'     => $form,
            ]
        );
    }
}
