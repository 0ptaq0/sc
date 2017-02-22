<?php

    namespace Inc\Modules\Blog;

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
                $this->core->lang['blog']['add_new']        => 'add',
                $this->core->lang['blog']['settings']		=> 'settings'
            ];
        }
        
        /**
        * list of posts
        */
        public function manage($page = 1)
        {
            if(isset($_POST['delete']))
            {
                if(isset($_POST['post-list']) && !empty($_POST['post-list']))
                {
                    foreach($_POST['post-list'] as $item)
                    {
                        $row = $this->core->db('blog')->where('id', $item)->oneArray();
                        if($this->core->db('blog')->delete($item))
                        {
                            if(file_exists(UPLOADS."/blog/".$row['cover_photo']))
                                unlink(UPLOADS."/blog/".$row['cover_photo']);

                            $this->core->setNotify('success', $this->core->lang['blog']['delete_success']);
                        }
                        else
                            $this->core->setNotify('failure', $this->core->lang['blog']['delete_failure']);
                    }

                    redirect(url([ADMIN, 'blog', 'manage']));
                }
            }
            
        	// pagination
			$totalRecords = count($this->core->db('blog')->toArray());
			$pagination = new \Inc\Engine\Lib\Pagination($page, $totalRecords, 10, url([ADMIN, 'blog', 'manage', '%d']));
            $this->assign['pagination'] = $pagination->nav();
            
            // list 
            $this->assign['newURL'] = url([ADMIN, 'blog', 'add']);
            $this->assign['postCount'] = 0;
            $rows = $this->core->db('blog')
                    ->limit($pagination->offset().', '.$pagination->getRecordsPerPage())
                    ->desc('published_at')->desc('created_at')
                    ->toArray();
                    
            if($totalRecords)
            {
                $this->assign['postCount'] = $totalRecords;
                foreach($rows as $row)
                {
                    $row['editURL'] = url([ADMIN, 'blog', 'edit', $row['id']]);
                    $row['delURL']  = url([ADMIN, 'blog', 'delete', $row['id']]);
                    $row['viewURL'] = url(['blog', 'post', $row['slug']]);


                    $row['user'] = $this->core->getUserInfo('username', $row['user_id']);
                    $row['comments'] = $row['comments'] ? $this->core->lang['blog']['comments_on'] : $this->core->lang['blog']['comments_off'];

                    switch($row['status'])
                    {
                        case 0:
                            $row['type'] = $this->core->lang['blog']['post_sketch'];
                            break;
                        case 1:
                            $row['type'] = $this->core->lang['blog']['post_hidden'];
                            break;
                        case 2:
                            $row['type'] = $this->core->lang['blog']['post_published'];
                            break;
                        default:
                            case 0:
                            $row['type'] = "Unknown";
                    }

                    $row['created_at'] = date("d-m-Y", $row['created_at']);
                    $row['published_at'] = date("d-m-Y", $row['published_at']);

                    $this->assign['posts'][] = $row;
                }
            }

            $this->core->tpl->set('blog', $this->assign);
            return $this->core->tpl->draw(MODULES.'/blog/view/admin/manage.html');
		}
		
        /**
        * add new post
        */
        public function add()
        {
            $this->assign['manageURL'] = url(['blog', 'manage']);
            $this->assign['editor'] = $this->core->getSettings('settings', 'editor');
            $this->_addHeaderFiles();
            
            $this->assign['form'] = ['title' => '', 'content' => '', 'slug' => '', 'intro' => '', 'date' => date('Y-m-d H:i'), 'comments' => 1, 'cover_photo' => NULL, 'status' => 0, 'markdown' => 0];

            $this->assign['users'] = $this->core->db('users')->toArray();
            $this->assign['author'] = $this->core->getUserInfo('id');
            
            $this->assign['title'] = $this->core->lang['blog']['new_post'];

            $this->core->tpl->set('blog', $this->assign);
            return $this->core->tpl->draw(MODULES.'/blog/view/admin/form.html');
        }
        
        
        /**
        * edit post
        */
        public function edit($id)
        {
            $this->assign['manageURL'] = url([ADMIN, 'blog', 'manage']);
            $this->assign['coverDeleteURL'] = url([ADMIN, 'blog', 'deleteCover', $id]);
            $this->assign['editor'] = $this->core->getSettings('settings', 'editor');
            $this->_addHeaderFiles();

            $blog = $this->core->db('blog')->where('id', $id)->oneArray();
			
        	if(!empty($blog))
        	{
                $this->assign['form'] = htmlspecialchars_array($blog);
                $this->assign['form']['content'] =  $this->core->tpl->noParse($this->assign['form']['content']);
                $this->assign['form']['date'] = date("Y-m-d H:i", $blog['published_at']);
                
                $this->assign['users'] = $this->core->db('users')->toArray();
                $this->assign['author'] = $this->core->getUserInfo('id', $blog['user_id']);

	        	$this->assign['title'] = $this->core->lang['blog']['edit_post'];

	        	$this->core->tpl->set('blog', $this->assign);
	        	return $this->core->tpl->draw(MODULES.'/blog/view/admin/form.html');
        	}
        	else
        		redirect(url([ADMIN, 'blog', 'manage']));
		}
		
        /**
        * save post
        */
	    public function save($id = null)
		{
            unset($_POST['save'], $_POST['files']);

            // redirect location
            if(!$id)
                $location = url([ADMIN, 'blog', 'add']);
            else
                $location = url([ADMIN, 'blog', 'edit', $id]);

    		if(checkEmptyFields(['title', 'content'], $_POST))
    		{
    			$this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
	            $this->assign['form'] = htmlspecialchars_array($_POST);
                $this->assign['form']['content'] = $this->core->tpl->noParse($this->assign['form']['content']);
                redirect($location);
    		}

            // slug
			if(empty($_POST['slug']))
                $_POST['slug'] = createSlug($_POST['title']);
			else
                $_POST['slug'] = createSlug($_POST['slug']);

            // check slug and append with iterator
            $oldSlug = $_POST['slug'];
            $i = 1;

            if($id === null)
                $id = 0;

            while($this->core->db('blog')->where('slug', $_POST['slug'])->where('id', '!=', $id)->oneArray())
            {
                $_POST['slug'] = $oldSlug.'-'.$i;
                $i++;
            }

            // format conversion date
            $_POST['updated_at'] = strtotime(date('Y-m-d H:i:s'));
            $_POST['published_at'] = strtotime($_POST['published_at']);
            if(!isset($_POST['comments'])) $_POST['comments'] = 0;
            if(!isset($_POST['markdown'])) $_POST['markdown'] = 0;

            if(isset($_FILES['cover_photo']['tmp_name']))
            {
                $img = new \Inc\Engine\Lib\Image;

                if($img->load($_FILES['cover_photo']['tmp_name']))
                {
                    if($img->getInfos('width') > 1000)
                        $img->resize(1000);
                    else if($img->getInfos('width') < 600)
                        $img->resize(600);

                    $_POST['cover_photo'] = $_POST['slug'].".".$img->getInfos('type');
                }

            }
            
            if(!$id) // new
            {
                $_POST['created_at'] = strtotime(date('Y-m-d H:i:s'));

                $query = $this->core->db('blog')->save($_POST);
                $location = url([ADMIN, 'blog', 'edit', $this->core->db()->pdo()->lastInsertId()]);
            }
            else    // edit
            {
                $query = $this->core->db('blog')->where('id', $id)->save($_POST);
            }

			if($query)
            {
                if(!file_exists(UPLOADS."/blog"))
                    mkdir(UPLOADS."/blog", 0777, true);

                $img->save(UPLOADS."/blog/".$_POST['cover_photo']);
				$this->core->setNotify('success', $this->core->lang['blog']['save_success']);
            }
			else
				$this->core->setNotify('failure', $this->core->lang['blog']['save_failure']);

            redirect($location);
		}
		
		/**
        * remove post
        */
		public function delete($id)
		{
			if($post = $this->core->db('blog')->where('id', $id)->oneArray() && $this->core->db('blog')->delete($id))
            {
                if($post['cover_photo'])
                    unlink(UPLOADS."/blog/".$post['cover_photo']);
				$this->core->setNotify('success', $this->core->lang['blog']['delete_success']);
            }
			else
				$this->core->setNotify('failure', $this->core->lang['blog']['delete_failure']);

			redirect(url([ADMIN, 'blog', 'manage']));
		}

		/**
        * remove post cover
        */
        public function deleteCover($id)
        {
            if($post = $this->core->db('blog')->where('id', $id)->oneArray())
            {
                unlink(UPLOADS."/blog/".$post['cover_photo']);
                $this->core->db('blog')->where('id', $id)->save(['cover_photo' => null]);
                $this->core->setNotify('success', $this->core->lang['blog']['cover_deleted']);

                redirect(url([ADMIN, 'blog', 'edit', $id]));
            }

        }

        public function settings()
        {
            $assign = htmlspecialchars_array($this->core->getSettings('blog'));
            $assign['dateformats'] = [
                [
                    'value' => 'd-m-Y',
                    'name'  => '01-01-2016'
                ],
                [
                    'value' => 'd/m/Y',
                    'name'  => '01/01/2016'
                ],
                [
                    'value' => 'd Mx Y',
                    'name'  => '01 '.$this->core->lang['blog']['janx'].' 2016'
                ],
                [
                    'value' => 'M d, Y',
                    'name'  => $this->core->lang['blog']['jan'].' 01, 2016'
                ],
                [
                    'value' => 'd-m-Y H:i',
                    'name'  => '01-01-2016 12:00'
                ],
                [
                    'value' => 'd/m/Y H:i',
                    'name'  => '01/01/2016 12:00'
                ],
                [
                    'value' => 'd Mx Y, H:i',
                    'name'  => '01 '.$this->core->lang['blog']['janx'].' 2016, 12:00'
                ],
            ];
            $this->core->tpl->set('settings', $assign);
            return $this->core->tpl->draw(MODULES.'/blog/view/admin/settings.html');
        }

        public function saveSettings()
        {
            foreach($_POST['blog'] as $key => $val)
            {
                $this->core->db('settings')->where('module', 'blog')->where('field', $key)->save(['value' => $val]);
            }
            $this->core->setNotify('success', $this->core->lang['blog']['settings_saved']);
            redirect(url([ADMIN, 'blog', 'settings']));
        }

		/**
        * image upload from WYSIWYG
        */
		public function editorUpload()
		{
			header('Content-type: application/json');
			$dir 	= UPLOADS.'/blog';
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
					$error = $this->core->lang['blog']['editor_upload_fail'];

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
			echo $this->core->tpl->draw(MODULES.'/blog/js/admin/blog.js');	
			exit();
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
			$this->core->addJS(url([ADMIN, 'blog', 'javascript']));
        }

    }