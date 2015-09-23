<?php
namespace Craft;

class EntryCountVariable
{	

	/**
	* @param $entryId
	* @return EntryCountModel
	*/
	public function getCount($entryId)
	{
		return craft()->entryCount->getCount($entryId);
	}

	/**
	* @return ElementCriteriaModel
	*/
	public function getEntries()
	{
		return craft()->entryCount->getEntries();
	}

	/**
	* @param $entryId
	*/
	public function increment($entryId)
	{
		craft()->entryCount->increment($entryId);
	}
}