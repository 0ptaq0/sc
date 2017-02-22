<?php

    namespace Inc\Modules\Galleries;

    class Admin
    {
        private $_thumbs = ['md' => 600, 'sm' => 300, 'xs' => 150];
        public $core;

        public function __construct($object)
        {
            $this->core = $object;
		}

        public function navigation()
        {
            return [
                $this->core->lang['general']['manage'] => 'manage',
            ];
        }

        /**
        * galleries manage
        */
        public function manage()
        {
            $assign = [];

	        // list
            $rows = $this->core->db('galleries')->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as $row)
	        	{
	        	    $row['tag']    = $this->core->tpl->noParse('{$gallery.'.$row['slug'].'}');
	        		$row['editURL'] = url([ADMIN, 'galleries',  'edit', $row['id']]);
		        	$row['delURL']  = url([ADMIN, 'galleries', 'delete', $row['id']]);

		        	$assign[] = $row;
	        	}
        	}

        	$this->core->tpl->set('galleries', $assign);
        	return $this->core->tpl->draw(MODULES.'/galleries/view/admin/manage.html');
        }

		/**
		* add new gallery
		*/
        public function add()
        {
            $location = [ADMIN, 'galleries', 'manage'];
            
            if(!empty($_POST['name']))
            {
                $name = trim($_POST['name']);
                if(!$this->core->db('galleries')->where('slug', createSlug($name))->count())
                {
                    $query = $this->core->db('galleries')->save(['name' => $name, 'slug' => createSlug($name)]);

                    if($query)
                    {
                        $id     = $this->core->db()->lastInsertId();
                        $dir    = UPLOADS.'/galleries/'.$id;

                        if(mkdir($dir, 0755, true))
                        {
                            $this->core->setNotify('success', $this->core->lang['galleries']['add_gallery_success']);
                            $location = [ADMIN, 'galleries', 'edit', $this->core->db()->lastInsertId()];
                        }
                    }
                    else
                        $this->core->setNotify('failure', $this->core->lang['galleries']['add_gallery_failure']);
                }
                else
                    $this->core->setNotify('failure', $this->core->lang['galleries']['gallery_already_exists']);
            }
            else
                $this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
                
            redirect(url($location));
        }

		/**
		* remove gallery
		*/
        public function delete($id)
        {
			$query = $this->core->db('galleries')->delete($id);

            deleteDir(UPLOADS.'/galleries/'.$id);

			if($query)
                $this->core->setNotify('success', $this->core->lang['galleries']['delete_gallery_success']);
			else
				$this->core->setNotify('failure', $this->core->lang['galleries']['delete_gallery_failure']);

            redirect(url([ADMIN, 'galleries', 'manage']));
        }

		/**
		* edit gallery
		*/
        public function edit($id, $page = 1)
        {
            $assign = [];
            $assign['settings'] = $this->core->db('galleries')->oneArray($id);

            // pagination
			$totalRecords = $this->core->db('galleries_items')->where('gallery', $id)->toArray();
			$pagination = new \Inc\Engine\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'galleries', 'edit', $id, '%d']));
			$assign['pagination'] = $pagination->nav();
            $assign['page'] = $page;

            // items
            if($assign['settings']['sort'] == 'ASC')
                $rows = $this->core->db('galleries_items')->where('gallery', $id)
                        ->limit($pagination->offset().', '.$pagination->getRecordsPerPage())
                        ->asc('id')->toArray();
            else
                $rows = $this->core->db('galleries_items')->where('gallery', $id)
                        ->limit($pagination->offset().', '.$pagination->getRecordsPerPage())
                        ->desc('id')->toArray();

            if(count($rows))
            {
                foreach($rows as $row)
                {
                    $row['title'] = $this->core->tpl->noParse(htmlspecialchars($row['title']));
                    $row['desc'] = $this->core->tpl->noParse(htmlspecialchars($row['desc']));
                    $row['src'] = unserialize($row['src']);

                    if(!isset($row['src']['sm']))
                        $row['src']['sm'] = $row['src']['xs'];

                    $assign['images'][] = $row;
                }
            }

            $assign['id'] = $id;
            $this->core->tpl->set('gallery', $assign);

            $this->core->addCSS(url('inc/jscripts/lightbox/lightbox.min.css'));
            $this->core->addJS(url('inc/jscripts/lightbox/lightbox.min.js'));
            $this->core->addJS(url('inc/jscripts/are-you-sure.min.js'));
            
            return $this->core->tpl->draw(MODULES.'/galleries/view/admin/edit.html');
        }

		/**
		* save gallery data
		*/
        public function saveSettings($id)
        {
            if(checkEmptyFields(['name', 'sort'], $_POST))
    		{
                $this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
                redirect(url([ADMIN, 'galleries', 'edit', $id]));
            }

            $_POST['slug'] = createSlug($_POST['name']);
            if($this->core->db('galleries')->where($id)->save($_POST))
                $this->core->setNotify('success', $this->core->lang['galleries']['save_settings_success']);

            redirect(url([ADMIN, 'galleries', 'edit', $id]));
        }

		/**
		* save images data
		*/
        public function saveImages($id, $page)
        {
            foreach($_POST['img'] as $key => $val)
            {
                $query = $this->core->db('galleries_items')->where($key)->save(['title' => $val['title'], 'desc' => $val['desc']]);
            }

            if($query)
                $this->core->setNotify('success', $this->core->lang['galleries']['save_settings_success']);

            redirect(url([ADMIN, 'galleries', 'edit', $id, $page]));
        }

        /**
        * image uploading
        */
        public function upload($id)
        {
            $dir    = UPLOADS.'/galleries/'.$id;
            $cntr   = 0;

            if(!is_uploaded_file($_FILES['files']['tmp_name'][0]))
                $this->core->setNotify('failure', $this->core->lang['galleries']['no_files']);
            else
            {
                foreach($_FILES['files']['tmp_name'] as $image)
                {
                    $img = new \Inc\Engine\Lib\Image();

                    if($img->load($image))
                    {
                        $imgName = time().$cntr++;
                        $imgPath = $dir.'/'.$imgName.'.'.$img->getInfos('type');
                        $src     = [];

                        // oryginal size
                        $img->save($imgPath);
                        $src['lg'] = str_replace(BASE_DIR.'/', null, $imgPath);

                        // generate thumbs
                        foreach($this->_thumbs as $key => $width)
                        {
                            if($img->getInfos('width') > $width)
                            {
                                $img->resize($width);
                                $img->save($thumbPath = "{$dir}/{$imgName}-{$key}.{$img->getInfos('type')}");
                                $src[$key] = str_replace(BASE_DIR.'/', null, $thumbPath);
                            }
                        }

                        $query = $this->core->db('galleries_items')->save(['src' => serialize($src), 'gallery' => $id]);
                    }
                    else
                        $this->core->setNotify('failure', $this->core->lang['galleries']['wrong_extension'], 'jpg, png, gif');
                }

    			if($query)
                    $this->core->setNotify('success', $this->core->lang['galleries']['add_images_success']);;
            }

            redirect(url([ADMIN, 'galleries', 'edit', $id]));
        }

        /**
        * remove image
        */
        public function deleteImage($id)
        {
            $image = $this->core->db('galleries_items')->where($id)->oneArray();
            if(!empty($image))
            {
                if($this->core->db('galleries_items')->delete($id))
                {
                    $images = unserialize($image['src']);
                    foreach($images as $src)
                    {
                        if(file_exists(BASE_DIR.'/'.$src))
                        {
                            if(!unlink(BASE_DIR.'/'.$src))
                                $this->core->setNotify('failure', $this->core->lang['galleries']['delete_image_failure']);
                            else
                                $this->core->setNotify('success', $this->core->lang['galleries']['delete_image_success']);
                        }
                    }
                }
            }
            else
                $this->core->setNotify('failure', $this->core->lang['galleries']['image_doesnt_exists']);

            redirect(url([ADMIN, 'galleries', 'edit', $image['gallery']]));
        }

    }