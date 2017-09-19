<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class TPersonalHomePage extends TObjetStd
{
	
	public function __construct()
	{
		global $conf;
		
		$this->set_table(MAIN_DB_PREFIX.'personalhomepage');
		
		$this->add_champs('url', array('type' => 'text'));
		$this->add_champs('element_type', array('type' => 'string', 'length' => '20'));
		
		$this->add_champs('entity,fk_element', array('type' => 'integer', 'index' => true));
		
		$this->_init_vars();
		$this->start();
		
		$this->entity = $conf->entity;
	}

	public static function getUrlFromUser(&$db, User &$user)
	{
		$PDOdb = new TPDOdb;
		$url = '';
		
		if (!class_exists('UserGroup')) require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
		
		$usergroup = new UserGroup($db);
		$TGroup = $usergroup->listGroupsForUser($user->id);
		foreach ($TGroup as &$usergroup)
		{
			$personalhomepage = new TPersonalHomePage;
			if ($personalhomepage->loadByGroupId($PDOdb, $usergroup->id) > 0 && !empty($personalhomepage->url))
			{
				$url = $personalhomepage->url;
				break;
			}
		}
		
		return $url;
	}
	
	public function loadByGroupId(&$PDOdb, $fk_group)
	{
		global $db;
		
		$sql = 'SELECT rowid FROM '.$this->get_table().' WHERE fk_element = '.$fk_group.' AND element_type = \'group\'';
		$resql = $db->query($sql);
		
		if ($resql)
		{
			if ($obj = $db->fetch_object($resql))
			{
				return $this->load($PDOdb, $obj->rowid);
			}
		}
		else
		{
			dol_print_error($db);
		}
		
		return 0;
	}
}