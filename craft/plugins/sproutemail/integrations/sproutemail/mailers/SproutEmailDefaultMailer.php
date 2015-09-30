<?php
namespace Craft;

class SproutEmailDefaultMailer extends SproutEmailBaseMailer implements SproutEmailNotificationSenderInterface
{
	/**
	 * @var SproutEmail_DefaultMailerService
	 */
	protected $service;

	/**
	 * @return SproutEmail_DefaultMailerService
	 */
	public function getService()
	{
		if (is_null($this->service))
		{
			$this->service = Craft::app()->getComponent('sproutEmail_defaultMailer');
		}

		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'defaultmailer';
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'Sprout Email';
	}

	/**
	 * @return null|string
	 */
	public function getDescription()
	{
		return Craft::t('Smart transactional email, easy recipient management, and advanced third party integrations.');
	}

	/**
	 * @param array $context
	 *
	 * @return \Twig_Markup
	 */
	public function getSettingsHtml(array $context = array())
	{
		if (!isset($context['settings']) || $context['settings'] === null)
		{
			$context['settings'] = $this->getSettings();
		}

		$html = craft()->templates->render('sproutemail/settings/_defaultmailer', $context);

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param $recipientListHandle
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel[]
	 */
	public function getRecipients($recipientListHandle)
	{
		if (($list = $this->getService()->getRecipientListByHandle($recipientListHandle)))
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModels($list->recipients);
		}
	}

	/**
	 * @param $id
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel|null
	 */
	public function getRecipientListById($id)
	{
		return $this->getService()->getRecipientListById($id);
	}

	/**
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists($this->getId());
	}

	public function defineSettings()
	{
		return array(
			'fromName'           => array(AttributeType::String, 'required' => true),
			'fromEmail'          => array(AttributeType::Email, 'required' => true),
			'replyTo'            => array(AttributeType::Email, 'required' => false),
			'enableDynamicLists' => array(AttributeType::Bool, 'default' => false),
		);
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return true;
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 */
	public function prepareRecipientLists(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_EntryRecipientListModel();

				$model->setAttribute('entryId', $entry->id);
				$model->setAttribute('mailer', $this->getId());
				$model->setAttribute('list', $id);
				$model->setAttribute('type', $campaign->type);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_EntryModel[] $values
	 *
	 * @return string|\Twig_Markup
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists    = $this->getRecipientLists();
		$options  = array();
		$selected = array();

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/settings/_defaultmailer-norecipients');
		}

		foreach ($lists as $list)
		{
			$options[] = array(
				'label' => $list->name,
				'value' => $list->id
			);
		}

		if (is_array($values) && count($values))
		{
			foreach ($values as $value)
			{
				$selected[] = $value->list;
			}
		}

		$html = craft()->templates->renderMacro(
			'_includes/forms', 'checkboxGroup', array(
				array(
					'id'      => 'recipientLists',
					'name'    => 'recipient[recipientLists]',
					'options' => $options,
					'values'  => $selected,
				)
			)
		);

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param SproutEmail_CampaignModel             $campaign
	 * @param BaseModel|BaseElementModel|array|null $element
	 *
	 * @return bool
	 */
	public function sendNotification(SproutEmail_CampaignModel $campaign, $element = null)
	{
		return $this->getService()->sendNotification($campaign, $element);
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @throws \Exception
	 *
	 * @return array
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		try
		{
			$this->getService()->exportEntry($entry, $campaign);

			$content = craft()->templates->render(
				'sproutemail/_modals/export',
				array(
					'entry'    => $entry,
					'campaign' => $campaign,
					'success'  => true,
				)
			);

			$success = true;

			return compact('content', 'success');
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());

			$content = craft()->templates->render(
				'sproutemail/_modals/export',
				array(
					'entry'    => $entry,
					'campaign' => $campaign,
					'success'  => false,
				)
			);

			$success = false;

			return compact('content', 'success');
		}
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 */
	public function previewEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$success = false;

		try
		{
			$this->getService()->exportEntry($entry, $campaign);

			$success = true;
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}

		$content = craft()->templates->render(
			'sproutemail/_modals/export',
			array(
				'entry'    => $entry,
				'campaign' => $campaign,
				'success'  => $success,
			)
		);

		return compact('content');
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$lists          = sproutEmail()->entries->getRecipientListsByEntryId($entry->id);
		$recipientLists = array();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$recipientList = sproutEmailDefaultMailer()->getRecipientListById($list->list);

				if ($recipientList)
				{
					$recipientLists[] = $recipientList;
				}
			}
		}

		return craft()->templates->render(
			'sproutemail/_modals/prepare',
			array(
				'entry'          => $entry,
				'campaign'       => $campaign,
				'recipientLists' => $recipientLists
			)
		);
	}
}
