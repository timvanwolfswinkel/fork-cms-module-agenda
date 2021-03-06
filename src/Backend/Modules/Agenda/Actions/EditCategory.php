<?php

namespace Backend\Modules\Agenda\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\File;


use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Language as BL;
use Backend\Modules\Agenda\Engine\Model as BackendAgendaModel;

/**
 * This is the edit category action, it will display a form to edit an existing category.
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Bram De Smyter <bram@bubblefish.be>
 */
class EditCategory extends BackendBaseActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        $this->id = $this->getParameter('id', 'int');

        // does the item exist?
        if ($this->id !== null && BackendAgendaModel::existsCategory($this->id)) {
            parent::execute();

            $this->getData();
            $this->loadForm();
            $this->validateForm();

            $this->parse();
            $this->display();
        } else {
            $this->redirect(BackendModel::createURLForAction('categories') . '&error=non-existing');
        }
    }

    /**
     * Get the data
     */
    private function getData()
    {
        $this->record = BackendAgendaModel::getCategory($this->id);
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        // create form
        $this->frm = new BackendForm('editCategory');
        $this->frm->addText('title', $this->record['title']);

        $this->meta = new BackendMeta($this->frm, $this->record['meta_id'], 'title', true);
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        parent::parse();

        // assign the data
        $this->tpl->assign('item', $this->record);
        $this->tpl->assign('showAgendaDeleteCategory',
            BackendAgendaModel::deleteCategoryAllowed($this->id) && BackendAuthentication::isAllowedAction('DeleteCategory'));
    }

    /**
     * Validate the form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->meta->setUrlCallback('Backend\Modules\Agenda\Engine\Model', 'getURLForCategory',
                array($this->record['id']));

            $this->frm->cleanupFields();

            // validate fields
            $this->frm->getField('title')->isFilled(BL::err('TitleIsRequired'));
            $this->meta->validate();

            if ($this->frm->isCorrect()) {
                // build item
                $item['id'] = $this->id;
                $item['language'] = $this->record['language'];
                $item['title'] = $this->frm->getField('title')->getValue();
                $item['meta_id'] = $this->meta->save(true);

                // update the item
                BackendAgendaModel::updateCategory($item);
                BackendModel::triggerEvent($this->getModule(), 'after_edit_category', array('item' => $item));

                // everything is saved, so redirect to the overview
                $this->redirect(BackendModel::createURLForAction('categories') . '&report=edited-category&var=' . urlencode($item['title']) . '&highlight=row-' . $item['id']);
            }
        }
    }
}
