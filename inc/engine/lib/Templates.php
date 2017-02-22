<?php

    namespace Inc\Engine\Lib;

    class Templates
    {

        private $data = [];
        private $tmp = 'tmp/';
        private $executes = [];
        private $tags = [
                    '{noparse}(.*?){\/noparse}' => 'self::noParse',
        			'{if: ([^}]*)}' => '<?php if ($1): ?>',
        			'{else}' => '<?php else: ?>',
        			'{elseif: ([^}]*)}' => '<?php elseif ($1): ?>',
        			'{\/if}' => '<?php endif; ?>',
        			'{loop: ([^}]*)}' => '<?php $counter = 0; foreach ($1 as $key => $value): ?>',
        			'{\/loop}' => '<?php $counter++; endforeach; ?>',
                    '{\?(\=){0,1}([^}]*)\?}' => '<?php if(strlen("$1")) echo $2; else $2; ?>',
        			'{(\$[a-zA-Z\-\._\[\]\'"0-9]+)}' => '<?php echo $1; ?>',
                    '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|e}' => '<?php echo htmlspecialchars($1, ENT_QUOTES | ENT_HTML5, "UTF-8"); ?>',
                    '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|cut:([0-9]+)}' => '<?php echo mb_strimwidth(strip_tags($1), 0, $2+3, "...", "utf-8"); ?>',
                    '{include: (.+?\.[a-z]{2,4})}' => '<?php include_once("$1"); ?>',
                    '{\*(.*?)\*}' => '',
        		];

        public $core;
        public function __construct($object)
        {
            $this->core = $object;
            if(!file_exists($this->tmp))
                mkdir($this->tmp);
        }

        /**
        * set variable
        * @param string $name
        * @param mixed $value
        * @return void
        */
        public function set($name, $value)
        {
            $this->data[$name] = $value;
        }

        /**
         * append array variable
         * @param  string $name
         * @param  mixed $value
         * @return void
         */
        public function append($name, $value)
        {
            $this->data[$name][] = $value;
        }

        /**
        * content parsing
        * @param string $content
        * @return string
        */
        private function parse($content)
        {
            // replace tags with PHP
            foreach($this->tags as $regexp => $replace)
            {
                if(strpos($replace, 'self') !== false)
                    $content = preg_replace_callback('#'.$regexp.'#s', $replace, $content);
                else
                    $content = preg_replace('#'.$regexp.'#', $replace, $content);
            }
            // replace variables
            if(preg_match_all('/(\$(?:[a-zA-Z0-9_-]+)(?:\.(?:(?:[a-zA-Z0-9_-][^\s]+)))*)/', $content, $matches))
            {   
                for($i = 0; $i < count($matches[1]); $i++)
                {
                    // $a.b to $a["b"]
                    $rep = preg_replace('/\.([a-zA-Z\-_0-9]*(?![a-zA-Z\-_0-9]*(\'|\")))/', "['$1']", $matches[1][$i]);
                    $content = str_replace($matches[0][$i], $rep, $content);
                }
            }


            return $content;
        }

        /**
        * execute PHP code
        * @param string $file
        * @return string
        */
        private function execute($file, $counter = 0)
        {
            $pathInfo = pathinfo($file);
            $tmpFile = $this->tmp.$pathInfo['basename'];

            if(!is_file($file))
            {
                echo "Template '$file' not found.";
            }
            else
            {
                $content = file_get_contents($file);

                if($this->searchTags($content) && ($counter < 3))
                {
                    file_put_contents($tmpFile, $content);
                    $content = $this->execute($tmpFile, ++$counter);
                }
                file_put_contents($tmpFile, $this->parse($content));

                extract($this->data, EXTR_SKIP);

                ob_start();
                include($tmpFile);
                if(!DEV_MODE) unlink($tmpFile);
                return ob_get_clean();
            }
        }

        /**
        * display compiled code
        * @param string $file
        * @param bool $last
        * @return string
        */
        public function draw($file, $last = false)
        {
            if(preg_match('#inc(\/modules\/[^"]*\/)view\/([^"]*.'.pathinfo($file, PATHINFO_EXTENSION).')#', $file, $m))
            {
                $themeFile = 'themes/'.$this->core->getSettings('settings', 'theme').$m[1].$m[2];
                if(is_file($themeFile)) $file = $themeFile;
            }

            $result = $this->execute($file);
            if(!$last) return $result;
            else
            {
    			$result = str_replace(['*bracket*','*/bracket*'], ['{', '}'], $result);
    			$result = str_replace('*dollar*', '$', $result);

                if(HTML_BEAUTY)
                {
                    $tidyHTML = new Indenter;
                    return $tidyHTML->indent($result);
                }
                return $result;
            }
        }

        /**
        * replace signs {,},$ in string with *words*
        * @param string $content
        * @return string
        */
        public function noParse($content)
        {
            if(is_array($content))
                $content = $content[1];
            $content = str_replace(['{', '}'], ['*bracket*', '*/bracket*'], $content);
            return str_replace('$', '*dollar*', $content);
        }

        /**
        * replace signs {,},$ in array with *words*
        * @param arry $array
        * @return array
        */
        public function noParse_array($array)
        {
            foreach($array as $key => $value)
            {
                if(is_array($value)) $array[$key] = $this->noParse_array($value);
                else $array[$key] = $this->noParse($value);
            }
            return $array;
        }

        /**
        * search tags in content
        * @param string $content
        * @return bool
        */
        private function searchTags($content)
        {
            foreach($this->tags as $regexp  => $replace)
            {
                if(preg_match('#'.$regexp.'#sU', $content, $matches))
                    return true;
            }
            return false;
        }

	}