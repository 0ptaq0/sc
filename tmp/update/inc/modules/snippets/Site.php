<?php

    namespace Inc\Modules\Snippets;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;
            $this->_importSnippets();
		}

        private function _importSnippets()
        {
            $rows = $this->core->db('snippets')->toArray();

            $snippets = [];
            foreach($rows as $row)
            {
                $snippets[$row['slug']] = $row['content'];
            }

            return $this->core->tpl->set('snippet', $snippets);
        }
    }