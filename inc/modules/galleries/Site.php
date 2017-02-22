<?php

    namespace Inc\Modules\Galleries;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;
            $this->_importGalleries();
		}

        private function _importGalleries()
        {
            $assign = []; $tempAssign = [];
            $galleries = $this->core->db('galleries')->toArray();

            if(count($galleries))
            {
                foreach($galleries as $gallery)
                {
                    if($gallery['sort'] == 'ASC')
                        $items = $this->core->db('galleries_items')->where('gallery', $gallery['id'])->asc('id')->toArray();
                    else
                        $items = $this->core->db('galleries_items')->where('gallery', $gallery['id'])->desc('id')->toArray();
                            
                    $tempAssign = $gallery;

                    if(count($items))
                    {
                        foreach($items as &$item)
                        {
                            $item['src'] = unserialize($item['src']);
                            if(!isset($item['src']['sm']))
                                $item['src']['sm'] = $item['src']['xs'];
                        }

                        $tempAssign['items'] = $items;
                        $this->core->tpl->set('gallery', $tempAssign);

                        $assign[$gallery['slug']] = $this->core->tpl->draw(MODULES.'/galleries/view/gallery.html');
                    }
                }
            }
            $this->core->tpl->set('gallery', $assign);

            $this->core->addCSS(url('inc/jscripts/lightbox/lightbox.min.css'));
            $this->core->addJS(url('inc/jscripts/lightbox/lightbox.min.js'));
        }

    }