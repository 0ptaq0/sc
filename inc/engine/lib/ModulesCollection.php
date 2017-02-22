<?php
	
	namespace Inc\Engine\Lib;

	class ModulesCollection
	{
		protected $modules = [];

		public function __construct($core)
		{
			$modules = array_column($core->db('modules')->asc('sequence')->toArray(), 'dir');

			foreach($modules as $dir)
			{
				$file = MODULES.'/'.$dir.'/Admin.php';
				if(file_exists($file))
				{
	                $clsName = 'Admin';
	        		$namespace = 'inc\modules\\'.$dir.'\\'.$clsName;
	                $this->modules[$dir] = new $namespace($core);
	            }
			}
		}

		public function getArray()
		{
			return $this->modules;
		}

		public function has($name)
		{
			return array_key_exists($name, $this->modules);
		}

		public function __get($module)
		{
			if(isset($this->modules[$module]))
				return $this->modules[$module];
			else
				return null;
		}
	}
