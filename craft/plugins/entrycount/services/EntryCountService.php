<?php
namespace Craft;

/**
* Class Name
*/

class EntryCountService
{

	/**
	* @param $entryId
	* @return EntryCountModel
	*/

	public function getCount($entryId)
	{
		// create new model
		$entryCountModel = new EntryCountModel();

		// get record from the database
		$entryCountRecord = EntryCountRecord::model()->findByAttributes(array('entryId' => $entryId));

		// if record exists, populate model from the record
		if($entryCountRecord)
		{
			$entryCountModel = EntryCountModel::populateModel($entryCountRecord);
		}

		return $entryCountModel;
	}

	/**
	* @return ElementCriteriaModel
	* @throws Exception
	*/
	public function getEntries()
	{
		// get all records from the Database, ordered by count descending
		$entryCountRecords = EntryCountRecord::model()->findAll(array('order' => 'count desc'));

		// get entry ids from records
		$entryIds = array();

		foreach ($entryCountRecords as $entryCountRecord)
		{
			$entryIds[] = $entryCountRecord->entryId;
		}

		// create criteria for entry element type
		$criteria = craft()->elements->getCriteria('Entry');

		// we filter by entry ids
		$criteria->id = $entryIds;

		// and enable fixed order
		$criteria->fixedOrder = true;

		return $criteria;

	}

	public function increment($entryId)
	{
		// check if this action should be ignored
		if ($this->_ignoreAction())
		{
			return;
		}

		// get the record from the database w/ the attribute of the passed in entry ID
		$entryCountRecord = EntryCountRecord::model()->findByAttributes(array('entryId' => $entryId));

		// if the record exists, then increment the count
		if ($entryCountRecord)
			{
				$entryCountRecord->setAttribute('count', $entryCountRecord->getAttribute('count') + 1);
			}
		// otherwise create a new record and set the count to 1	
		else
			{
				$entryCountRecord = new EntryCountRecord();
				$entryCountRecord->entryId = $entryId;
				$entryCountRecord->count = 1;
			}

		// save the record in the database	
		$entryCountRecord->save();
	}

	public function reset($entryId)
	{
		// get record from the database
		$entryCountRecord = EntryCountRecord::model()->findByAttributes(array('entryId' => $entryId));

		// if the record exists, delete it from the database
		if ($entryCountRecord)
		{
			$entryCountRecord->delete();
		}
	}

	private function _ignoreAction()
	{
		$settings = craft()->plugins->getPlugin('entrycount')->getSettings();

		if ($settings->ignoreLoggedInUsers AND craft()->userSession->isLoggedIn())
			{
				return true;
			}

		if ($settings->ignoreIpAddresses AND in_array(craft()->request->getIpAddress(), explode("\n", $settings->ignoreIpAddresses)))
			{
				return true;
			}

		return false;	
	}
}

