<?php

    namespace Inc\Modules\Settings;

    class Admin
    {
        public $core;
        private $assign = [];
        private $feed_url = "http://feed.sruu.pl";

        public function __construct($object)
        {
            $this->core = $object;
        }

        public function navigation()
        {
            return [
                $this->core->lang['settings']['general']    => 'general',
                $this->core->lang['general']['theme']       => 'theme',
                $this->core->lang['settings']['updates']    => 'updates',
            ];
        }

        public function general()
        {
            $settings = $this->core->getSettings('settings');

            // lang
            if(isset($_GET['lang']) && !empty($_GET['lang']))
                $lang = $_GET['lang'];
            else
                $lang = $settings['lang_site'];

            $settings['langs'] = $this->_getLanguages();
            $settings['themes'] = $this->_getThemes();
            $settings['pages'] = $this->_getPages($lang);

            foreach($this->core->getRegisteredPages() as $page)
                $settings['pages'][] = $page;
            
            if(!empty($redirectData = getRedirectData()))
                $settings = array_merge($settings, $redirectData);

            $this->core->tpl->set('settings', $this->core->tpl->noParse_array(htmlspecialchars_array($settings)));
            
            $this->core->tpl->set('updateurl', url([ADMIN, 'settings', 'updates']));

            return $this->core->tpl->draw(MODULES.'/settings/view/admin/general.html');
        }

        public function saveGeneral()
        {
            unset($_POST['save']);
            if(checkEmptyFields(array_keys($_POST), $_POST))
            {
                $this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
                redirect(url([ADMIN, 'settings', 'general']), $_POST);
            }
            else
            {
                $errors = 0;
                foreach($_POST as $field => $value)
                {
                    if(!$this->core->db('settings')->where('module', 'settings')->where('field', $field)->save(['value' => $value]))
                    $errors++;
                }

                if(!$errors)
                    $this->core->setNotify('success', $this->core->lang['settings']['save_settings_success']);
                else
                    $this->core->setNotify('failure', $this->core->lang['settings']['save_settings_failure']);

                redirect(url([ADMIN, 'settings', 'general']));
            }
        }

        public function theme($theme = null, $file = null)
        {
            if(empty($theme) && empty($file))
            {
                $this->core->tpl->set('settings', $this->core->getSettings('settings'));
                $this->core->tpl->set('themes', $this->_getThemes());
                return $this->core->tpl->draw(MODULES.'/settings/view/admin/themes.html');
            }
            else
            {
                if($file == 'activate')
                {
                    $this->core->db('settings')->where('module', 'settings')->where('field', 'theme')->save(['value' => $theme]);
                    $this->core->setNotify('success', $this->core->lang['settings']['theme_changed']);
                    redirect(url([ADMIN, 'settings', 'theme']));
                }
                $this->core->addCSS(url('/inc/jscripts/markitup/skin/style.css'));
                $this->core->addCSS(url('/inc/jscripts/markitup/set/style.css'));
                $this->core->addJS(url('/inc/jscripts/markitup/markitup.min.js'));
                $this->core->addJS(url('inc/jscripts/markitup/set/'.$this->core->getSettings('settings', 'lang_admin').'.js'));

                $this->assign['files'] = $this->_getThemeFiles($file, $theme);

                if($file)
                    $file = $this->assign['files'][$file]['path'];
                else
                    $file = reset($this->assign['files'])['path'];

                $this->assign['content'] = $this->core->tpl->noParse(htmlspecialchars(file_get_contents($file)));

                if(isset($_POST['save']) && !FILE_LOCK)
                {
                    if(file_put_contents($file, htmlspecialchars_decode($_POST['content'])))
                        $this->core->setNotify('success', $this->core->lang['settings']['save_file_success']);
                    else
                        $this->core->setNotify('failure', $this->core->lang['settings']['save_file_failure']);

                    redirect(url([ADMIN, 'settings', 'theme', $theme, md5($file)]));
                }

                $this->core->tpl->set('settings', $this->core->getSettings('settings'));
                $this->core->tpl->set('theme', array_merge($this->_getThemes($theme), $this->assign));
                return $this->core->tpl->draw(MODULES.'/settings/view/admin/theme.html');
            }
        }

        public function updates()
        {
            $this->core->tpl->set('allow_curl', intval(function_exists('curl_init')));
            $settings = $this->core->getSettings('settings');
            
            if(isset($_POST['check']))
            {
                $request = $this->updateRequest('/batflat/update', [
                    'ip' => $_SERVER['SERVER_ADDR'],
                    'version' => $settings['version'],
                    'domain' => url(),
                ]);

                $this->_updateSettings('update_check', time());

                if(!is_array($request))
                    $this->core->tpl->set('error', $request);
                else if($request['status'] == 'error')
                    $this->core->tpl->set('error', $request['message']);
                else
                {
                    $this->_updateSettings('update_version', $request['data']['version']);
                    $this->_updateSettings('update_changelog', $request['data']['changelog']);
                    $this->core->tpl->set('update_version', $request['data']['version']);

                    // if(DEV_MODE)
                    //     $this->core->tpl->set('request', $request);
                }
            }
            else if(isset($_POST['update']))
            {
                if(!class_exists("\ZipArchive"))
                    $this->core->tpl->set('error', "ZipArchive is required to update Batflat.");

                if(!isset($_GET['manual']))
                {
                    $request = $this->updateRequest('/batflat/update', [
                        'ip' => $_SERVER['SERVER_ADDR'],
                        'version' => $settings['version'],
                        'domain' => url(),
                    ]);

                    $this->download($request['data']['download'], BASE_DIR.'/tmp/latest.zip');
                }
                else
                {
                    $package = glob(BASE_DIR.'/batflat-*.zip');
                    if(!empty($package))
                    {
                        $package = array_shift($package);
                        $this->rcopy($package, BASE_DIR.'/tmp/latest.zip');
                    }
                }

                define("UPGRADABLE", true);
                // Making backup
                $backup_date = date('YmdHis');
                $this->rcopy(BASE_DIR, BASE_DIR.'/backup/'.$backup_date.'/', 0755, [BASE_DIR.'/backup', BASE_DIR.'/tmp/latest.zip', (isset($package) ? BASE_DIR.'/'.basename($package) : '')]);

                // Unzip latest update
                $zip = new \ZipArchive;
                $zip->open(BASE_DIR.'/tmp/latest.zip');
                $zip->extractTo(BASE_DIR.'/tmp/update');

                // Copy files
                $this->rcopy(BASE_DIR.'/tmp/update/inc/css', BASE_DIR.'/inc/css');
                $this->rcopy(BASE_DIR.'/tmp/update/inc/engine', BASE_DIR.'/inc/engine');
                $this->rcopy(BASE_DIR.'/tmp/update/inc/jscripts', BASE_DIR.'/inc/jscripts');
                $this->rcopy(BASE_DIR.'/tmp/update/inc/lang', BASE_DIR.'/inc/lang');
                $this->rcopy(BASE_DIR.'/tmp/update/inc/modules', BASE_DIR.'/inc/modules');

                // Run upgrade script
                $version = $settings['version'];
                $new_version = include(BASE_DIR.'/tmp/update/upgrade.php');

                // Close archive and delete all unnecessary files
                $zip->close();
                unlink(BASE_DIR.'/tmp/latest.zip');
                deleteDir(BASE_DIR.'/tmp/update');

                $this->_updateSettings('version', $new_version);
                $this->_updateSettings('update_version', 0);
                $this->_updateSettings('update_changelog', '');
                $this->_updateSettings('update_check', time());
            }
            else if(isset($_GET['reset']))
            {
                $this->_updateSettings('update_version', 0);
                $this->_updateSettings('update_changelog', '');
                $this->_updateSettings('update_check', 0);
            }
            else if(isset($_GET['manual']))
            {
                $package = glob(BASE_DIR.'/batflat-*.zip');
                $version = false;
                if(!empty($package))
                {
                    $package_path = array_shift($package);
                    preg_match('/batflat\-([0-9\.a-z]+)\.zip$/', $package_path, $matches);
                    $version = $matches[1];
                }
                
                $manual_mode = ['version' => $version];
            }


            $settings = $this->core->getSettings('settings');
            $this->core->tpl->set('settings', $settings);
            $this->core->tpl->set('manual_mode', isset_or($manual_mode, false));
            return $this->core->tpl->draw(MODULES.'/settings/view/admin/update.html');
        }

        public function changeOrderOfNavItem()
        {
            foreach($_POST as $module => $order)
                $this->core->db('modules')->where('dir', $module)->save(['sequence' => $order]);
            exit();
        }

        private function updateRequest($resource, $params = [])
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->feed_url.$resource);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec ($ch);
            if($output === FALSE)
                $output = curl_error($ch);
            else
                $output = json_decode($output, true);

            curl_close ($ch);

            return $output;
        }

        private function download($source, $dest)
        {
            set_time_limit(0);
            $fp = fopen($dest, 'w+');
            $ch = curl_init($source);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch); 
            curl_close($ch);
            fclose($fp);
        }
        
        /**
        * list of languages
        * @return array
        */
        private function _getLanguages()
        {
            $langs = glob('../inc/lang/*', GLOB_ONLYDIR);

            foreach($langs as &$lang)
                $lang = basename($lang);

            return $langs;
        }

        /**
        * list of themes
        * @return array
        */
        private function _getThemes($theme = null)
        {
            $themes = glob(THEMES.'/*', GLOB_ONLYDIR);
            $return = [];
            foreach($themes as $e)
            {
                if($e != THEMES.'/admin')
                {
                    $manifest = array_fill_keys(['name', 'version', 'author', 'email', 'thumb'], 'Unknown');
                    $manifest['name'] = basename($e);
                    $manifest['thumb'] = '../admin/img/unknown_theme.png';
                    
                    if(file_exists($e.'/manifest.json'))
                        $manifest = array_merge($manifest, json_decode(file_get_contents($e.'/manifest.json'), true));

                    if($theme == basename($e))
                        return array_merge($manifest, ['dir' => basename($e)]);

                    $return[] = array_merge($manifest, ['dir' => basename($e)]);
                }
            }

            return $return;
        }

        /**
        * list of pages
        * @param string $lang
        * @param integer $selected
        * @return array
        */
        private function _getPages($lang)
        {
            $rows = $this->core->db('pages')->where('lang', $lang)->toArray();
            if(count($rows))
            {
                foreach($rows as $row)
                {
                    $result[] = ['id' => $row['id'], 'title' => $row['title'], 'slug' => $row['slug']];
                }
            }
            return $result;
        }

        /**
        * list of theme files (html, css & js)
        * @param string $selected
        * @return array
        */
        private function _getThemeFiles($selected = null, $theme = null)
        {
            $theme = ($theme ? $theme : $this->core->getSettings('settings', 'theme'));
            $files = $this->rglob(THEMES.'/'.$theme.'/*.html');
            $files = array_merge($files, $this->rglob(THEMES.'/'.$theme.'/*.css'));
            $files = array_merge($files, $this->rglob(THEMES.'/'.$theme.'/*.js'));

            $result = [];
            foreach($files as $file)
            {
                if($selected && ($selected == md5($file)))
                    $attr = 'selected';
                else
                    $attr = null;

                $result[md5($file)] = ['name' => basename($file), 'path' => $file, 'short' => str_replace(BASE_DIR, null, $file), 'attr' => $attr];
            }

            return $result;
        }

        private function _updateSettings($field, $value)
        {
            $this->core->db('settings')->where('module', 'settings')->where('field', $field)->save(['value' => $value]);
        }

        private function rcopy($source, $dest, $permissions = 0755, $expect = [])
        {
            foreach($expect as $e)
            {
                if($e == $source) return;
            }

            if (is_link($source)) {
                return symlink(readlink($source), $dest);
            }

            if (is_file($source)) {
                if(!is_dir(dirname($dest)))
                    mkdir(dirname($dest), 0777, true);

                return copy($source, $dest);
            }

            if (!is_dir($dest)) {
                mkdir($dest, $permissions, true);
            }

            $dir = dir($source);
            while (false !== $entry = $dir->read()) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                $this->rcopy("$source/$entry", "$dest/$entry", $permissions, $expect);
            }

            $dir->close();
            return true;
        }

        private function rglob($pattern, $flags = 0)
        {
            $files = glob($pattern, $flags); 
            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                $files = array_merge($files, $this->rglob($dir.'/'.basename($pattern), $flags));
            }
            return $files;
        }

    }