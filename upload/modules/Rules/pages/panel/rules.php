<?php 
/*
 *	Rules module made by Coldfire
 *	https://coldfiredzn.com
 *
 *	Using code from the vote module by Partydragen and Samerton
 */
 
if($user->isLoggedIn()){
	if(!$user->canViewACP()){
		// No
		Redirect::to(URL::build('/'));
		die();
	}
	if(!$user->isAdmLoggedIn()){
		// Needs to authenticate
		Redirect::to(URL::build('/panel/auth'));
		die();
	} else {
		if(!$user->hasPermission('admincp.rules')){
			require_once(ROOT_PATH . '/404.php');
			die();
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
	die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'rules');
define('PANEL_PAGE', 'rules');
$page_title = $rules_language->get('rules', 'rules');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if(!isset($_GET['action'])){
	if(Input::exists()){
		$errors = array();
		if(Token::check(Input::get('token'))){
			$validate = new Validate();
			$validation = $validate->check($_POST, array(
				'message' => array(
					'required' => true,
					'max' => 2048
				),
				'link_location' => array(
					'required' => true
				),
				'icon' => array(
					'max' => 64
				)
			));
						
			if($validation->passed()){			
				try {
					if(isset($_POST['link_location'])){
						switch($_POST['link_location']){
							case 1:
							case 2:
							case 3:
							case 4:
								$location = $_POST['link_location'];
								break;
						default:
						$location = 1;
						}
					} else
					$location = 1;
									
					$cache->setCache('nav_location');
					$cache->store('rules_location', $location);
								
					$cache->setCache('navbar_icons');
					$cache->store('rules_icon', Input::get('icon'));
								
					$message_id = $queries->getWhere('rules_settings', array('name', '=', 'rules_message'));
					$message_id = $message_id[0]->id;
					$queries->update('rules_settings', $message_id, array(
						'value' => Input::get('message'),
					));

				} catch(Exception $e){
					$errors[] = $e->getMessage();
				}
			} else {
				$errors[] = $rules_language->get('rules', 'message_maximum');
			}
		} else {
			$errors[] = $language->get('general', 'invalid_token');
		}
	}
	
	$rules_catagories = $queries->getWhere('rules_catagories', array('id', '<>', 0));
	$catagories_array = array();
	if(count($rules_catagories)){
		foreach($rules_catagories as $catagory){
			$catagories_array[] = array(
				'edit_link' => URL::build('/panel/rules/', 'action=edit&id=' . Output::getClean($catagory->id)),
				'name' => Output::getClean($catagory->name),
				'delete_link' => URL::build('/panel/rules/', 'action=delete&id=' . Output::getClean($catagory->id))
			);
		}
	}

	$rules_buttons = $queries->getWhere('rules_buttons', array('id', '<>', 0));
	$buttons_array = array();
	if(count($rules_buttons)){
		foreach($rules_buttons as $button){
			$buttons_array[] = array(
				'edit_link' => URL::build('/panel/rules/', 'action=edit_button&id=' . Output::getClean($button->id)),
				'name' => Output::getClean($button->name),
				'delete_link' => URL::build('/panel/rules/', 'action=delete_button&id=' . Output::getClean($button->id))
			);
		}
	}

	$cache->setCache('nav_location');
	$link_location = $cache->retrieve('rules_location');

	$cache->setCache('navbar_icons');
	$icon = $cache->retrieve('rules_icon');

	$rules_message = $queries->getWhere('rules_settings', array('name', '=', "rules_message"));
	$rules_message = htmlspecialchars($rules_message[0]->value);

	$smarty->assign(array(
		'NEW_BUTTON' => $rules_language->get('rules', 'new_button'),
		'RULES_BUTTON_NAME' => $rules_language->get('rules', 'rules_button_name'),
		'NEW_BUTTON_LINK' => URL::build('/panel/rules/', 'action=new_button'),
		'BUTTON_LIST' => $buttons_array,
		'NO_RULES_BUTTONS' => $rules_language->get('rules', 'no_rules_buttons'),
		'CONFIRM_DELETE_BUTTON' => $rules_language->get('rules', 'delete_button'),

		'NEW_CATAGORY' => $rules_language->get('rules', 'new_catagory'),
		'RULES_CATAGORY_NAME' => $rules_language->get('rules', 'rules_catagory_name'),
		'RULES_ACTION' => $rules_language->get('rules', 'rules_action'),
		'NEW_CATAGORY_LINK' => URL::build('/panel/rules/', 'action=new'),
		'LINK_LOCATION' => $rules_language->get('rules', 'link_location'),
		'LINK_LOCATION_VALUE' => $link_location,
		'LINK_NAVBAR' => $language->get('admin', 'page_link_navbar'),
		'LINK_MORE' => $language->get('admin', 'page_link_more'),
		'LINK_FOOTER' => $language->get('admin', 'page_link_footer'),
		'LINK_NONE' => $language->get('admin', 'page_link_none'),
		'ICON' => $rules_language->get('rules', 'icon'),
		'ICON_EXAMPLE' => htmlspecialchars($rules_language->get('rules', 'icon_example')),
		'ICON_VALUE' => Output::getClean(htmlspecialchars_decode($icon)),
		'CATAGORY_LIST' => $catagories_array,
		'NO_RULES_CATAGORIES' => $rules_language->get('rules', 'no_rules_catagories'),
		'MESSAGE' => $rules_language->get('rules', 'message'),
		'MESSAGE_VALUE' => $rules_message,
		'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
		'CONFIRM_DELETE_CATAGORY' => $rules_language->get('rules', 'delete_catagory'),
		'YES' => $language->get('general', 'yes'),
		'NO' => $language->get('general', 'no')
	));
	
	$template_file = 'rules/rules.tpl';
} else {
	switch($_GET['action']){
		case 'new':
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'rules_catagory_name' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						),
						'rules_catagory_icon' => array(
							'max' => 96
						),
						'rules_catagory_rules' => array(
							'required' => true,
							'min' => 1
						)
					));
								
					if($validation->passed()){
						// input into database
						try {
							$queries->create('rules_catagories', array(
								'name' => htmlspecialchars(Input::get('rules_catagory_name')),
								'icon' => htmlspecialchars(Input::get('rules_catagory_icon')),
								'rules' => htmlspecialchars(Input::get('rules_catagory_rules'))
							));
							Session::flash('staff_rules', $rules_language->get('rules', 'catagory_created_successfully'));
							Redirect::to(URL::build('/panel/rules'));
							die();
						} catch(Exception $e){
							$errors[] = $e->getMessage();
						}
					} else {
						foreach($validation->errors() as $item){
							if(strpos($item, 'is required') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_required');

								else if(strpos($item, 'rules_catagory_rules') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_rules_required');
							} else if(strpos($item, 'minimum') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_minimum');

								else if(strpos($item, 'rules_catagory_rules') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_rules_minimum');
							} else if(strpos($item, 'maximum') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_maximum');

								else if(strpos($item, 'rules_catagory_icon') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_icon_maximum');
							}
						}
					}
				} else {
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
						
			$smarty->assign(array(
				'NEW_CATAGORY' => $rules_language->get('rules', 'new_catagory'),
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/panel/rules'),
				'RULES_CATAGORY_NAME' => $rules_language->get('rules', 'rules_catagory_name'),
				'RULES_CATAGORY_ICON' => $rules_language->get('rules', 'rules_catagory_icon'),
				'RULES_CATAGORY_RULES' => $rules_language->get('rules', 'rules_catagory_rules'),
			));
			
			$template_file = 'rules/rules_new.tpl';
		break;
		case 'edit':
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
			$catagory = $queries->getWhere('rules_catagories', array('id', '=', $_GET['id']));
			if(!count($catagory)){
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
			$catagory = $catagory[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'rules_catagory_name' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						),
						'rules_catagory_icon' => array(
							'max' => 96
						),
						'rules_catagory_rules' => array(
							'required' => true,
							'min' => 1
						)
					));
								
					if($validation->passed()){
						try {
							$queries->update('rules_catagories', $catagory->id, array(
								'name' => htmlspecialchars(Input::get('rules_catagory_name')),
								'icon' => htmlspecialchars(Input::get('rules_catagory_icon')),
								'rules' => htmlspecialchars(Input::get('rules_catagory_rules'))
							));
							Session::flash('staff_rules', $rules_language->get('rules', 'catagory_updated_successfully'));
							Redirect::to(URL::build('/panel/rules'));
							die();
						} catch(Exception $e){
							$errors[] = $e->getMessage();
						}
					} else {
						foreach($validation->errors() as $item){
							if(strpos($item, 'is required') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_required');

								else if(strpos($item, 'rules_catagory_rules') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_rules_required');
							} else if(strpos($item, 'minimum') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_minimum');

								else if(strpos($item, 'rules_catagory_rules') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_rules_minimum');
							} else if(strpos($item, 'maximum') !== false){
								if(strpos($item, 'rules_catagory_name') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_name_maximum');

								else if(strpos($item, 'rules_catagory_icon') !== false)
									$errors[] = $rules_language->get('rules', 'catagory_icon_maximum');
							}
						}
					}
				} else {
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
						
			$smarty->assign(array(
				'EDIT_CATAGORY' => $rules_language->get('rules', 'edit_catagory'),
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/panel/rules'),
				'RULES_CATAGORY_NAME' => $rules_language->get('rules', 'rules_catagory_name'),
				'RULES_CATAGORY_NAME_VALUE' => Output::getClean($catagory->name),
				'RULES_CATAGORY_ICON' => $rules_language->get('rules', 'rules_catagory_icon'),
				'RULES_CATAGORY_ICON_VALUE' => Output::getClean($catagory->icon),
				'RULES_CATAGORY_RULES' => $rules_language->get('rules', 'rules_catagory_rules'),
				'RULES_CATAGORY_RULES_VALUE' => Output::getClean($catagory->rules),
			));
		
			$template_file = 'rules/rules_edit.tpl';
		break;
		case 'delete':
			if(isset($_GET['id']) && is_numeric($_GET['id'])){
				try {
					$queries->delete('rules_catagories', array('id', '=', $_GET['id']));
				} catch(Exception $e){
					die($e->getMessage());
				}

				Session::flash('staff_rules', $rules_language->get('rules', 'catagory_deleted_successfully'));
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
		break;
		case 'new_button':
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'rules_button_name' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						),
						'rules_button_link' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						)
					));
								
					if($validation->passed()){
						// input into database
						try {
							$queries->create('rules_buttons', array(
								'name' => htmlspecialchars(Input::get('rules_button_name')),
								'link' => htmlspecialchars(Input::get('rules_button_link'))
							));
							Session::flash('staff_rules', $rules_language->get('rules', 'button_created_successfully'));
							Redirect::to(URL::build('/panel/rules'));
							die();
						} catch(Exception $e){
							$errors[] = $e->getMessage();
						}
					} else {
						foreach($validation->errors() as $item){
							if(strpos($item, 'is required') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_required');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_required');
							} else if(strpos($item, 'minimum') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_minimum');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_minimum');
							} else if(strpos($item, 'maximum') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_maximum');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_maximum');
							}
						}
					}
				} else {
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
						
			$smarty->assign(array(
				'NEW_BUTTON' => $rules_language->get('rules', 'new_button'),
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/panel/rules'),
				'RULES_BUTTON_NAME' => $rules_language->get('rules', 'rules_button_name'),
				'RULES_BUTTON_LINK' => $rules_language->get('rules', 'rules_button_link')
			));
			
			$template_file = 'rules/button_new.tpl';
		break;
		case 'edit_button':
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
			$button = $queries->getWhere('rules_buttons', array('id', '=', $_GET['id']));
			if(!count($button)){
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
			$button = $button[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'rules_button_name' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						),
						'rules_button_link' => array(
							'required' => true,
							'min' => 1,
							'max' => 96
						)
					));
								
					if($validation->passed()){
						try {
							$queries->update('rules_buttons', $button->id, array(
								'name' => htmlspecialchars(Input::get('rules_button_name')),
								'link' => htmlspecialchars(Input::get('rules_button_link'))
							));
							Session::flash('staff_rules', $rules_language->get('rules', 'button_updated_successfully'));
							Redirect::to(URL::build('/panel/rules'));
							die();
						} catch(Exception $e){
							$errors[] = $e->getMessage();
						}
					} else {
						foreach($validation->errors() as $item){
							if(strpos($item, 'is required') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_required');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_required');
							} else if(strpos($item, 'minimum') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_minimum');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_minimum');
							} else if(strpos($item, 'maximum') !== false){
								if(strpos($item, 'rules_button_name') !== false)
									$errors[] = $rules_language->get('rules', 'button_name_maximum');

								else if(strpos($item, 'rules_button_link') !== false)
									$errors[] = $rules_language->get('rules', 'button_link_maximum');
							}
						}
					}
				} else {
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
						
			$smarty->assign(array(
				'EDIT_BUTTON' => $rules_language->get('rules', 'edit_button'),
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/panel/rules'),
				'RULES_BUTTON_NAME' => $rules_language->get('rules', 'rules_button_name'),
				'RULES_BUTTON_NAME_VALUE' => Output::getClean($button->name),
				'RULES_BUTTON_LINK' => $rules_language->get('rules', 'rules_button_link'),
				'RULES_BUTTON_LINK_VALUE' => Output::getClean($button->link)
			));
		
			$template_file = 'rules/button_edit.tpl';
		break;
		case 'delete_button':
			if(isset($_GET['id']) && is_numeric($_GET['id'])){
				try {
					$queries->delete('rules_buttons', array('id', '=', $_GET['id']));
				} catch(Exception $e){
					die($e->getMessage());
				}

				Session::flash('staff_rules', $rules_language->get('rules', 'button_deleted_successfully'));
				Redirect::to(URL::build('/panel/rules'));
				die();
			}
		break;
		default:
			Redirect::to(URL::build('/panel/rules'));
			die();
		break;
	}
}
			
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

if(Session::exists('staff_rules'))
	$success = Session::flash('staff_rules');

if(isset($success))
	$smarty->assign(array(
		'SUCCESS' => $success,
		'SUCCESS_TITLE' => $language->get('general', 'success')
	));

if(isset($errors) && count($errors))
	$smarty->assign(array(
		'ERRORS' => $errors,
		'ERRORS_TITLE' => $language->get('general', 'error')
	));

$smarty->assign(array(
	'PARENT_PAGE' => PARENT_PAGE,
	'PAGE' => PANEL_PAGE,
	'DASHBOARD' => $language->get('admin', 'dashboard'),
	'RULES' => $rules_language->get('rules', 'rules'),
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

$template->displayTemplate($template_file, $smarty);