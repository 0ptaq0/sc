<?php

    namespace Inc\Modules\Carousel;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;

            $this->core->tpl->set('carousel', $this->_insertCarousels());
        }

        private function _insertCarousels()
        {
            $assign = []; $tempAssign = [];
            $galleries = $this->core->db('galleries')->toArray();

            if(!empty($galleries))
            {
                foreach($galleries as $gallery)
                {
                    $items = $this->core->db('galleries_items')->where('gallery', $gallery['id'])->toArray();
                    $tempAssign = $gallery;

                    if(count($items))
                    {
                        foreach($items as &$item)
                        {
                            $item['src'] = unserialize($item['src']);
                        }

                        $tempAssign['items'] = $items;
                        $this->core->tpl->set('carousel', $tempAssign);

                        $assign[$gallery['slug']] = $this->core->tpl->draw(MODULES.'/carousel/view/carousel.html');
                    }

                }
            }

            return $assign;
        }

    }