<?php
/**
 * Project management controller's.
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package admin
 * @subpackage project
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AdminadminController.php';

class ProjectadminController extends AdminadminController
{
	protected function getProjectData($data)
	{
		if (!isset($data["projects_name"]) || !isset($data["projects_description"])) {
			return array();
		}

		$project = array();
		$project["projects_name"] = $data["projects_name"];
		$project["projects_description"] = $data["projects_description"];
		return $project;
	}

	public function indexAction()
	{
		$table = new USVN_Db_Table_Projects();
		if ($this->_request->getParam('folder') != null) {
			$folder = str_replace(USVN_URL_SEP, '/', $this->_request->getParam('folder'));
			$i = strripos(substr($folder, 0, -1), '/', 2);
			$this->view->prev = ($i === false ? '' : substr($folder, 0, $i + 1));
			$projects = $table->fetchAll("projects_name LIKE '{$folder}%'", "projects_name");
		} else {
			$folder = '';
			$projects = $table->fetchAll(null, "projects_name");
		}
		$this->view->prefix = $folder;
		$tmp_projects = array();
		$tmp_folders = array();
		foreach ($projects as $project) {
			$tmp_project = substr($project->name, strlen($folder));
			if (strstr($tmp_project, '/') === false) {
				$tmp_projects[$tmp_project] = $project->description;
			} elseif (preg_match('#^([^/]+/).*#', $tmp_project, $tmp) && !in_array($tmp[1], $tmp_folders)) {
				$tmp_folders[$tmp[1]] = '';
			}
		}
		ksort($tmp_folders);
		ksort($tmp_projects);
		$this->view->projects = array_merge($tmp_folders, $tmp_projects);
	}

	public function newAction()
	{
		$table = new USVN_Db_Table_Projects();
		$this->view->project = $table->createRow();
		$folder = str_replace(USVN_URL_SEP, '/', $this->_request->getParam('folder'));
		$this->view->prefix = $folder;
	}

	public function createAction()
	{
		$data = $this->getProjectData($_POST);
		if (empty($data)) {
			$this->_redirect("/admin/project/new");
		}
		try {
			$identity = Zend_Auth::getInstance()->getIdentity();
			USVN_Project::createProject($data, $identity['username'], $_POST['creategroup'], $_POST['addmetogroup'], $_POST['admin'], $_POST['createsvndir']);
			$this->_redirect("/admin/project/");
		}
		catch (USVN_Exception $e) {
			$this->view->message = nl2br($e->getMessage());
			$this->newAction();
			$this->view->project->setFromArray($data);
			$this->render('new');
		}
	}

	public function editAction()
	{
		//rechercher projet + users
		$identity = Zend_Auth::getInstance()->getIdentity();

		$user_table = new USVN_Db_Table_Users();
		$users = $user_table->fetchRow(array('users_login = ?' => $identity['username']));

		$table = new USVN_Db_Table_Projects();
		$this->view->project = $table->fetchRow(array('projects_name = ?' => str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'))));

		$table = new USVN_Db_Table_UsersToProjects();
		$UserToProject = $table->fetchRow(array('users_id = ?' => $users->users_id, 'projects_id = ?' => $this->view->project->projects_id));
		if ($UserToProject !== null) {
			$this->view->AdminProject = 1;
		}
		
		if ($this->view->project === null) {
			$this->_redirect("/admin/project/");
		}
	}

	public function updateAction()
	{
		$data = $this->getProjectData($_POST);
		if (empty($data)) {
			$this->_redirect("/admin/project/new");
		}
		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchRow(array("projects_name = ?" => str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name'))));
		if ($project === null) {
			$this->_redirect("/admin/project/");
		}

		$identity = Zend_Auth::getInstance()->getIdentity();
		$user_table = new USVN_Db_Table_Users();
		$users = $user_table->fetchRow(array('users_login = ?' => $identity['username']));

		if (isset($_POST['admin'])) {
			$table->AddUserToProject($users, $project);
		}
		else {
			$table->DeleteUserToProject($users, $project);
		}

		$project->setFromArray($data);
		try {
			$project->save();
			$this->_redirect("/admin/project/");
		}
		catch (Exception $e) {
			$this->view->project = $project;
			$this->view->message = nl2br($e->getMessage());
			$this->render('edit');
		}
	}

	public function deleteAction()
	{
		USVN_Project::deleteProject(str_replace(USVN_URL_SEP, '/', $this->getRequest()->getParam('name')));
		$this->_redirect("/admin/project/");
	}
}
