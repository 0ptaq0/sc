<?php

    namespace Inc\Modules\Pages;

    class Admin
    {

        public $core;
        private $assign = [];
        
        public function __construct($object)
        {
            $this->core = $object;
		}

        public function navigation()
        {
            return [
                $this->core->lang['general']['manage']		=> 'manage',
                $this->core->lang['pages']['add_new']		=> 'add'
            ];
        }
        
        /**
        * list of pages
        */
        public function manage($page = 1)
        {
        	// lang
        	if(isset($_GET['lang']) && !empty($_GET['lang']))
        		$lang = $_GET['lang'];
			else
				$lang = $this->core->getSettings('settings', 'lang_site');

        	// pagination
			$totalRecords = $this->core->db('pages')->where('lang', $lang)->toArray();
			$pagination = new \Inc\Engine\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'pages', 'manage', '%d']));
			$this->assign['pagination'] = $pagination->nav();
			
			// list
        	$rows = $this->core->db('pages')->where('lang', $lang)
					->limit($pagination->offset().', '.$pagination->getRecordsPerPage())
					->toArray();
					
        	if(count($rows))
        	{
	        	foreach($rows as $row)
	        	{
	        		$row['editURL'] = url([ADMIN, 'pages', 'edit', $row['id']]);
		        	$row['delURL']  = url([ADMIN, 'pages', 'delete', $row['id']]);
		        	$row['viewURL'] = url($row['slug']);

		        	$this->assign['list'][] = $row;
	        	}
        	}
        	
        	$this->assign['langs'] = $this->_getLanguages($lang);
        	$this->core->tpl->set('pages', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/pages/view/admin/manage.html');
		}
		
        /**
        * add new page
        */
        public function add()
        {
            $this->assign['editor'] = $this->core->getSettings('settings', 'editor');
            $this->_addHeaderFiles();
        	
            // Unsaved data with failure
            if(!empty($e = getRedirectData()))
            {
                $this->assign['form'] = ['title' => isset_or($e['title'], ''), 'desc' => isset_or($e['desc'], ''), 'content' => isset_or($e['content'], ''), 'slug' => isset_or($e['slug'], '')];
            }
            else
        	   $this->assign['form'] = ['title' => '', 'desc' => '', 'content' => '', 'slug' => '', 'markdown' => 0];
        	
        	$this->assign['title'] = $this->core->lang['pages']['new_page'];
        	$this->assign['langs'] = $this->_getLanguages($this->core->getSettings('settings', 'lang_site'));
        	$this->assign['templates'] = $this->_getTemplates(isset_or($e['template'], null));

        	$this->core->tpl->set('pages', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/pages/view/admin/form.html');
		}
		
		
        /**
        * edit page
        */
        public function edit($id)
        {
            $this->assign['editor'] = $this->core->getSettings('settings', 'editor');
            $this->_addHeaderFiles();

            $page = $this->core->db('pages')->where('id', $id)->oneArray();
			
        	if(!empty($page))
        	{
                // Unsaved data with failure
                if(!empty($e = getRedirectData()))
                {
                    $this->assign['form'] = ['title' => isset_or($e['title'], ''), 'desc' => isset_or($e['desc'], ''), 'content' => isset_or($e['content'], ''), 'slug' => isset_or($e['slug'], '')];
                }
                else
                {
                    $this->assign['form'] = htmlspecialchars_array($page);
                    $this->assign['form']['content'] =  $this->core->tpl->noParse($this->assign['form']['content']);
                }

	        	$this->assign['title'] = $this->core->lang['pages']['edit_page'];
	        	$this->assign['langs'] = $this->_getLanguages($page['lang']);
	        	$this->assign['templates'] = $this->_getTemplates($page['template']);

	        	$this->core->tpl->set('pages', $this->assign);
	        	return $this->core->tpl->draw(MODULES.'/pages/view/admin/form.html');
        	}
        	else
        		redirect(url([ADMIN, 'pages', 'manage']));
		}
		
        /**
        * save data
        */
	    public function save($id = null)
		{
            unset($_POST['save'], $_POST['files']);

            if(!$id)
                $location = url([ADMIN, 'pages', 'new']);
            else
                $location = url([ADMIN, 'pages', 'edit', $id]);

    		if(checkEmptyFields(['title', 'content', 'lang', 'template'], $_POST))
    		{
    			$this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
	            $this->assign['form'] = htmlspecialchars_array($_POST);
                $this->assign['form']['content'] = $this->core->tpl->noParse($this->assign['form']['content']);
                redirect($location);
    		}

			$_POST['title'] = trim($_POST['title']);
			if(!isset($_POST['markdown'])) $_POST['markdown'] = 0;
			
			if(empty($_POST['slug']))
                $_POST['slug'] = createSlug($_POST['title']);
			else
                $_POST['slug'] = createSlug($_POST['slug']);

            if($id != null && $this->core->db('pages')->where('slug', $_POST['slug'])->where('lang', $_POST['lang'])->where('id', '!=', $id)->oneArray())
            {
                $this->core->setNotify('failure', $this->core->lang['pages']['page_exists']);
                redirect(url([ADMIN, 'pages', 'edit', $id]), $_POST);
            }
            else if($id == null && $this->core->db('pages')->where('slug', $_POST['slug'])->where('lang', $_POST['lang'])->oneArray())
            {
                $this->core->setNotify('failure', $this->core->lang['pages']['page_exists']);
                redirect(url([ADMIN, 'pages', 'add']), $_POST);
            }

			if(!$id)
			{
                $_POST['date'] = date('Y-m-d H:i:s');
                $query = $this->core->db('pages')->save($_POST);
                $location = url([ADMIN, 'pages', 'edit', $this->core->db()->pdo()->lastInsertId()]);
			}
			else
                $query = $this->core->db('pages')->where('id', $id)->save($_POST);

			if($query)
				$this->core->setNotify('success', $this->core->lang['pages']['save_success']);
			else
				$this->core->setNotify('failure', $this->core->lang['pages']['save_failure']);

            redirect($location);
		}
		
		/**
        * remove page
        */
		public function delete($id)
		{
			if($this->core->db('pages')->delete($id))
				$this->core->setNotify('success', $this->core->lang['pages']['delete_success']);
			else
				$this->core->setNotify('failure', $this->core->lang['pages']['delete_failure']);

			redirect(url([ADMIN, 'pages', 'manage']));
		}


		/**
        * image upload from WYSIWYG
        */
		public function editorUpload()
		{
			header('Content-type: application/json');
			$dir 	= UPLOADS.'/pages';
			$error 	= null;

			if(!file_exists($dir))
            	mkdir($dir, 0777, true);

		    if(isset($_FILES['file']['tmp_name']))
            {
				$img = new \Inc\Engine\Lib\Image;
				
				if($img->load($_FILES['file']['tmp_name']))
                {
					$imgPath = $dir.'/'.time().'.'.$img->getInfos('type');
					$img->save($imgPath);
					echo json_encode(['status' => 'success', 'result' => url($imgPath)]);	
				} 
				else
					$error = $this->core->lang['pages']['editor_upload_fail'];

				if($error)
					echo json_encode(['status' => 'failure', 'result' => $error]);
			}
			exit();
		}

		/**
        * module JavaScript
        */
		public function javascript()
		{
			header('Content-type: text/javascript');
			echo $this->core->tpl->draw(MODULES.'/pages/js/admin/pages.js');	
			exit();
		}

		/**
		* lista of languages
		* @param string $selected
		* @return array
		*/
		private function _getLanguages($selected = null)
		{
			$langs = glob('../inc/lang/*', GLOB_ONLYDIR);
			
			$result = [];
			foreach($langs as $lang)
			{
				if($selected == basename($lang)) $attr = 'selected';
				else $attr = null;
				$result[] = ['name' => basename($lang), 'attr' => $attr];
			}
			return $result;
		}
		
		/**
		* list of theme's templates
		* @param string $selected
		* @return array
		*/
		private function _getTemplates($selected = null)
		{
			$theme = $this->core->getSettings('settings', 'theme');
			$tpls = glob(THEMES.'/'.$theme.'/*.html');
			
			$result = [];
			foreach($tpls as $tpl)
			{
				if($selected == basename($tpl)) $attr = 'selected';
				else $attr = null;
				$result[] = ['name' => basename($tpl), 'attr' => $attr];
			}
			return $result;
		}

        private function _addHeaderFiles()
        {
            // WYSIWYG
            $this->core->addCSS(url('inc/jscripts/wysiwyg/summernote.min.css'));
        	$this->core->addJS(url('inc/jscripts/wysiwyg/summernote.min.js'));
            if($this->core->getSettings('settings', 'lang_admin') != 'en_english')
                $this->core->addJS(url('inc/jscripts/wysiwyg/lang/'.$this->core->getSettings('settings', 'lang_admin').'.js'));
            
			// HTML EDITOR
            $this->core->addCSS(url('inc/jscripts/markitup/skin/style.css'));
            $this->core->addCSS(url('inc/jscripts/markitup/set/style.css'));
            $this->core->addJS(url('inc/jscripts/markitup/markitup.min.js'));
            $this->core->addJS(url('inc/jscripts/markitup/set/'.$this->core->getSettings('settings', 'lang_admin').'.js'));
			
			// ARE YOU SURE?
			$this->core->addJS(url('inc/jscripts/are-you-sure.min.js'));

			// MODULE SCRIPTS
			$this->core->addJS(url([ADMIN, 'pages', 'javascript']));
        }

    }