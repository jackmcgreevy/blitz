<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerController
 *
 * @package Craft
 */
class SproutEmail_DefaultMailerController extends BaseController
{
	public function actionExport()
	{
		$attributes = craft()->httpSession->get('__exportJob');

		craft()->httpSession->remove('__exportJob');

		if ($attributes && ($recipients = craft()->elements->getCriteria('SproutEmail_DefaultMailerRecipient', $attributes)->find()))
		{
			$this->generateCsvExport($recipients);

			craft()->end();
		}

		craft()->userSession->setError(Craft::t('Nothing to export.'));
		craft()->request->redirect(UrlHelper::getCpUrl('sproutemail/recipients'));
	}

	/**
	 * @param SproutEmail_DefaultMailerRecipientModel[] $elements
	 * @param string                                    $filename
	 * @param string                                    $delimiter
	 *
	 * @return bool
	 */
	protected function generateCsvExport(array $elements, $filename = 'recipients.csv', $delimiter = ',')
	{
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'";');

		$f = fopen('php://output', 'w');

		foreach ($elements as $element)
		{
			fputcsv(
				$f,
				array(
					$element->firstName,
					$element->lastName,
					$element->email,
				),
				$delimiter
			);
		}

		fclose($f);
	}

	/**
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionShowEditRecipientTemplate(array $variables = array())
	{
		$variables['title']              = Craft::t('Recipient');
		$variables['recipientListsHtml'] = null;

		if (isset($variables['id']))
		{
			$variables['element'] = sproutEmailDefaultMailer()->getRecipientById($variables['id']);

			if (!$variables['element'])
			{
				throw new HttpException(404);
			}

			$selectedLists = $variables['element']->getRecipientListIds();

			$variables['recipientListsHtml'] = sproutEmailDefaultMailer()->getRecipientListsHtml($selectedLists);
		}
		else
		{
			$selectedLists = array();

			if (craft()->request->getParam('recipientListId'))
			{
				$selectedLists[] = craft()->request->getParam('recipientListId');
			}

			$variables['title']              = Craft::t('New Recipient');
			$variables['element']            = new SproutEmail_DefaultMailerRecipientModel();
			$variables['recipientListsHtml'] = sproutEmailDefaultMailer()->getRecipientListsHtml($selectedLists);
		}

		$variables['recipientLists']     = sproutEmailDefaultMailer()->getRecipientLists();
		$variables['continueEditingUrl'] = isset($variables['id']) ? 'sproutemail/_recipients/dit/'.$variables['id'] : null;

		$this->renderTemplate('sproutemail/_recipients/edit', $variables);
	}

	/**
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionShowIndexRecipientTemplate(array $variables = array())
	{
		if (isset($variables['recipientListId']))
		{
			/**
			 * @var $recipientList SproutEmail_DefaultMailerRecipientListModel
			 */
			$recipientList = sproutEmailDefaultMailer()->getRecipientListById($variables['recipientListId']);

			if (!$recipientList)
			{
				throw new HttpException(404);
			}

			$recipients = $recipientList->recipients;
		}
		else
		{
			$recipients = sproutEmailDefaultMailer()->getRecipients();
		}

		$variables['title']          = Craft::t('Recipients');
		$variables['recipientLists'] = sproutEmailDefaultMailer()->getRecipientLists();
		$variables['recipients']     = $recipients;

		$this->renderTemplate('sproutemail/_recipients/index', $variables);
	}

	/**
	 * @throws \Exception
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionSaveRecipient()
	{
		$this->requirePostRequest();

		$id = craft()->request->getPost('id');

		if ($id && is_numeric($id))
		{
			$model = sproutEmailDefaultMailer()->getRecipientById($id);

			if (!$model)
			{
				throw new Exception(Craft::t('Recipient with id ({id}) was not found.', array('id' => $id)));
			}
		}
		else
		{
			$model = new SproutEmail_DefaultMailerRecipientModel();
		}

		$model->setAttributes(craft()->request->getPost('recipient'));

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipient($model))
		{
			craft()->userSession->setNotice(Craft::t('Recipient saved successfully.'));

			$this->redirectToPostedUrl($model);
			craft()->end();
		}

		craft()->userSession->setError(Craft::t('Unable to save recipient.'));
		craft()->urlManager->setRouteVariables(array('recipient' => $model));
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionSaveRecipientList()
	{
		$this->requirePostRequest();

		$id = craft()->request->getPost('id');

		if ($id && is_numeric($id))
		{
			$model = sproutEmailDefaultMailer()->getRecipientListById($id);

			if (!$model)
			{
				throw new Exception(Craft::t('Recipient list with id ({id}) was not found.', array('id' => $id)));
			}
		}
		else
		{
			$model = new SproutEmail_DefaultMailerRecipientListModel();
		}

		$name = craft()->request->getPost('name', $model->name);

		$model->setAttribute('name', $name);
		$model->setAttribute('handle', sproutEmail()->createHandle($name));

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipientList($model))
		{
			craft()->userSession->setNotice(Craft::t('Recipient list saved successfully.'));

			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(
					array(
						'success' => 'true',
						'list'    => array(
							'id' => $model->id
						)
					)
				);
			}

			$this->redirectToPostedUrl($model);
		}

		craft()->userSession->setError(Craft::t('Unable to save recipient list.'));

		if (craft()->request->isAjaxRequest())
		{
			$this->returnErrorJson(Craft::t('Unable to save recipient list.'));
		}

		craft()->urlManager->setRouteVariables(array('recipientList' => $model));
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionDeleteRecipient()
	{
		$this->requirePostRequest();

		$id    = craft()->request->getRequiredPost('id');
		$model = null;

		if (($model = sproutEmailDefaultMailer()->getRecipientById($id)))
		{
			if (!$model)
			{
				throw new Exception(Craft::t('Recipient with id ({id}) was not found.', array('id' => $id)));
			}

			$vars    = array('recipientId' => $model->id);
			$deleted = SproutEmail_DefaultMailerRecipientRecord::model()->deleteByPk($model->id);

			if ($deleted)
			{
				SproutEmail_DefaultMailerRecipientListRecipientRecord::model()->deleteAllByAttributes($vars);

				craft()->userSession->setNotice(Craft::t('Recipient deleted successfully.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Unable to delete recipient.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnErrorJson(Craft::t('Unable to delete recipient.'));
				}
			}

			$this->redirectToPostedUrl($model);
		}

		throw new HttpException(404);
	}

	public function actionDeleteRecipientList()
	{
		$this->requirePostRequest();

		$id    = craft()->request->getRequiredPost('id');
		$model = null;

		if (($model = sproutEmailDefaultMailer()->getRecipientListById($id)))
		{
			if (!$model)
			{
				throw new Exception(Craft::t('Recipient list with id ({id}) was not found.', array('id' => $id)));
			}

			$vars    = array('recipientListId' => $model->id);
			$deleted = SproutEmail_DefaultMailerRecipientListRecord::model()->deleteByPk($model->id);

			if ($deleted)
			{
				SproutEmail_DefaultMailerRecipientListRecipientRecord::model()->deleteAllByAttributes($vars);

				craft()->userSession->setNotice(Craft::t('Recipient list deleted successfully.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Unable to delete recipient list.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnErrorJson(Craft::t('Unable to delete recipient list.'));
				}
			}

			$this->redirectToPostedUrl($model);
		}

		throw new HttpException(404);
	}
}
