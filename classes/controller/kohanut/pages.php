<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Pages Controller
 *
 * @package    Kohanut
 * @author     Michael Peters
 * @copyright  (c) Michael Peters
 * @license    http://kohanut.com/license
 */
class Controller_Kohanut_Pages extends Controller_Kohanut_Admin {
	
	public function action_index()
	{
		
		$this->view->title = "Pages";
		$this->view->body = new View('kohanut/pages/list');
		
		// build the page tree
		
		$root = Sprig_Mptt::factory('kohanut_page');
		$root->lft = 1;
		$root->load(); //->root();
		if ( ! $root->loaded())
		{
			die ('root node could not be loaded');
		}
		
		$this->view->body->list = $root->render_descendants('kohanut/pages/mptt',true,'ASC',10);
		//$this->view->body->list = $root->name;
		
	}
	
	public function action_meta($id)
	{
		// Find the page
		$page = Sprig::factory('kohanut_page',array('id'=>$id))->load();

		if ( ! $page->loaded())
		{
			return $this->admin_error("Could not find page with id <strong>" . (int) $id . "</strong>");
		}
		
		$errors = false;
		$success = false;
		
		if ($_POST)
		{
			try
			{
				$page->values($_POST);
				$page->update();
				$success = "Updated successfully";
			}
			catch (Validate_Exception $e)
			{
				$errors = $e->array->errors('page');
			}
		}
		
		$this->view->title = "Edit Page";
		$this->view->body = new View('kohanut/pages/edit');
		
		$this->view->body->page = $page;
		$this->view->body->errors = $errors;
		$this->view->body->success = $success;
	}
	
	public function action_edit($id)
	{
		// Find the page
		$page = Sprig::factory('kohanut_page',array('id'=>$id))->load();

		if ( ! $page->loaded())
		{
			return $this->admin_error("Could not find page with id <strong>" . (int) $id . "</strong>");
		}
		
		// If this page is an external link, redirect to meta
		if ($page->islink)
			$this->request->redirect(Route::get('kohanut-admin')->uri(array('controller'=>'pages','action'=>'meta','params'=>$id)));
			
		if ($_POST)
		{
			// redirect to adding a new element
			$this->request->redirect(Route::get('kohanut-admin')->uri(array('controller'=>'elements','action'=>'add','params'=>Arr::get($_POST,'type',NULL) .'/'. $id .'/' . Arr::get($_POST,'area',NULL))));
		}
		
		// Make it so the usual admin stuff is not shown (as in the header and main nav)
		$this->auto_render = false;
		
		// Make it so the admin pane for pages is shown
		Kohanut::$adminmode = true;
		Kohanut::style( Route::get('kohanut-media')->uri(array('file'=>'css/page.css')));
		
		// Render the page
		$this->request->response = $page->render();
		
	}
	
	public function action_add($id)
	{
		// Find the page
		$page = Sprig::factory('kohanut_page',array('id'=>$id))->load();

		if ( ! $page->loaded())
		{
			return $this->admin_error("Could not find page with id <strong>" . (int) $id . "</strong>");
		}
		
		$newpage = Sprig::factory('kohanut_page');
		
		$errors = false;
		
		// check for submit
		if ($_POST)
		{
			try
			{
				$newpage->values($_POST);
				
				// where are we putting it?
				$location = Arr::get($_POST,'location','last');
				if ($location == 'first')
				{
					$newpage->insert_as_first_child($page);
				}
				else if ($location == 'last')
				{
					$newpage->insert_as_last_child($page);
				}
				else
				{
					$target = Sprig::factory('kohanut_page',array('id'=> (int) $location))->load();
					if ( ! $target->loaded())
					{
						return $this->admin_error("Could not find target for insert_as_next_sibling id: " . (int) $location);
					}
					$newpage->insert_as_next_sibling($target);
				}
				
				// page created successfully, redirect to edit
				$this->request->redirect(Route::get('kohanut-admin')->uri(array('controller'=>'pages','action'=>'edit','params'=>$newpage->id)));
				
			}
			catch (Validate_Exception $e)
			{
				$errors = $e->array->errors('page');
			}
		}
		
		$this->view->title="Add Page";
		$this->view->body = new View('kohanut/pages/add');
		
		$this->view->body->errors = $errors;
		$this->view->body->parent = $page;
		$this->view->body->page = $newpage;
		
	}
	
	public function action_move($id)
	{
		// Find the page
		$page = Sprig::factory('kohanut_page',array('id'=>$id))->load();

		if ( ! $page->loaded())
		{
			return $this->admin_error("Could not find page with id <strong>" . (int) $id . "</strong>");
		}
		
		if ($_POST)
		{
			// Find the target
			$target = Sprig::factory('kohanut_page',array('id'=> (int) $_POST['target'] ))->load();
			
			// Make sure it exists
			if ( !$target->loaded())
			{
				return $this->admin_error("Could not find target page id " . (int) $_POST['target']);
			}
			
			$action = $_POST['action'];
			
			if ($action == 'before')
				$page->move_to_prev_sibling($target);
			elseif ($action == 'after')
				$page->move_to_next_sibling($target);
			elseif ($action == 'first')
				$page->move_to_first_child($target);
			elseif ($action == 'last')
				$page->move_to_last_child($target);
			else
				return $this->admin_error("move action was unknown. switch statement failed.");
				
			$this->request->redirect(Route::get('kohanut-admin')->uri(array('controller'=>'pages')));
			
		}
		$this->view->title = "Move Page";
		$this->view->body = new View('kohanut/pages/move');
		
		$this->view->body->page = $page;
	}
	
	public function action_delete($id)
	{
		// Find the page
		$page = Sprig::factory('kohanut_page',array('id'=>$id))->load();

		if ( ! $page->loaded())
		{
			return $this->admin_error("Could not find page with id <strong>" . (int) $id . "</strong>");
		}
		
		if ($_POST)
		{
			if (Arr::get($_POST,'submit',FALSE))
			{
				$page->delete();
				$this->request->redirect(Route::get('kohanut-admin')->uri(array('controller'=>'pages')));
			}
		}
		
		$this->view->title="Delete Page";
		$this->view->body = new View('kohanut/pages/delete');
		$this->view->body->page = $page;
		
	}
	
}