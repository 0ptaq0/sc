<?php

    namespace Inc\Modules\Blog;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;

            $this->core->router->set('blog', function() {
                $this->_importAllPosts();
            });
            $this->core->router->set('blog/(:int)', function($page) {
                $this->_importAllPosts($page);
            });
            $this->core->router->set('blog/post/(:str)', function($slug) {
                $this->_importPost($slug);
            });
            $this->core->router->set('blog/tag/(:str)', function($tag) {
                $this->_importTagPosts($tag);
            });
            $this->core->router->set('blog/tag/(:str)/(:int)', function($tag, $page) {
                $this->_importTagPosts($tag, $page);
            });
		}

        /**
        * get single post data
        */
        public function _importPost($slug = null)
        {
            $assign = [];
            if(!empty($slug))
            {
                if($this->core->loginCheck())
                    $row = $this->core->db('blog')->where('slug', $slug)->oneArray();
                else
                    $row = $this->core->db('blog')->where('status', '>=', 1)->where('published_at', '<=', time())->where('slug', $slug)->oneArray();

                if(!empty($row))
                {
                    // get dependences
                    $row['author'] = $this->core->db('users')->where('id', $row['user_id'])->oneArray();
                    $row['cover_url'] = url('uploads/blog/'.$row['cover_photo']).'?'.$row['published_at'];

                    $row['url'] = url('blog/post/'.$row['slug']);

                    // tags
                    $row['tags'] = $this->core->db('blog_tags')
                                        ->leftJoin('blog_tags_relationship', 'blog_tags.id = blog_tags_relationship.tag_id')
                                        ->where('blog_tags_relationship.blog_id', $row['id'])
                                        ->toArray();
                    if($row['tags'])
                    {
                        array_walk($row['tags'], function(&$tag) {
                            $tag['url'] = url('blog/tag/'.$tag['slug']);
                        });
                    }
                    
                    $assign = $row;

                    // Markdown
                    if(intval($assign['markdown']))
                    {
                        $parsedown = new \Inc\Core\Lib\Parsedown();
                        $assign['content'] = $parsedown->text($assign['content']);
                        $assign['intro'] = $parsedown->text($assign['intro']);
                    }
                    
                    // Admin access only
                    if($this->core->loginCheck())
                    {
                        if($assign['published_at'] > time())
                            $assign['content'] = '<div class="alert alert-warning">'.$this->core->lang['blog']['post_time'].'</div>'.$assign['content'];
                        if($assign['status'] == 0)
                            $assign['content'] = '<div class="alert alert-warning">'.$this->core->lang['blog']['post_draft'].'</div>'.$assign['content'];
                    }

                    // date formatting
                    $assign['published_at'] = (new \DateTime(date("YmdHis", $assign['published_at'])))->format( $this->core->getSettings('blog','dateformat'));
                    $keys = array_keys($this->core->lang['blog']);
                    $vals = array_values($this->core->lang['blog']);
                    $assign['published_at'] = str_replace($keys, $vals, strtolower($assign['published_at']));

                    $this->core->template = "post.html";
                    $this->core->tpl->set('page', ['title' => $assign['title'], 'desc' => trim(mb_strimwidth(htmlspecialchars(strip_tags(preg_replace('/\{(.*?)\}/', null, $assign['content']))), 0, 155, "...", "utf-8"))]);
                    $this->core->tpl->set('post', $assign);
                    $this->core->tpl->set('blog', [
                        'title' => $this->core->getSettings('blog', 'title'),
                        'desc' => $this->core->getSettings('blog', 'desc')
                    ]);
                }
                else
                {
                    header('HTTP/1.0 404 Not Found');

                    if($row = $this->_get404())
                        $assign = $row;
                    else
                    {
                        echo '<h1>404 Not Found</h1>';
                        echo $this->core->lang['blog']['not_found'];
                        exit;
                    }

                    $this->core->template = $row['template'];
                    $this->core->tpl->set('page', $assign);
                }
            }

            $this->core->append('<meta name="generator" content="Batflat" />', 'header');
        }

        /**
        * get array with all posts
        */
        public function _importAllPosts($page = 1)
        {
            $page = max($page, 1);
            $perpage = $this->core->getSettings('blog', 'perpage');
            $rows = $this->core->db('blog')->where('status', 2)->where('published_at', '<=', time())->limit($perpage)->offset(($page-1)*$perpage)->desc('published_at')->toArray();

            $assign = [
                'title' => $this->core->getSettings('blog', 'title'),
                'desc' => $this->core->getSettings('blog', 'desc'),
                'posts' => []
            ];
            foreach($rows as $row)
            {
                // get dependences
                $row['author'] = $this->core->db('users')->where('id', $row['user_id'])->oneArray();
                $row['cover_url'] = url('uploads/blog/'.$row['cover_photo']).'?'.$row['published_at'];

                // tags
                $row['tags'] = $this->core->db('blog_tags')
                                    ->leftJoin('blog_tags_relationship', 'blog_tags.id = blog_tags_relationship.tag_id')
                                    ->where('blog_tags_relationship.blog_id', $row['id'])
                                    ->toArray();
                                    
                if($row['tags'])
                {
                    array_walk($row['tags'], function(&$tag) {
                        $tag['url'] = url('blog/tag/'.$tag['slug']);
                    });
                }
                
                // date formatting
                $row['published_at'] = (new \DateTime(date("YmdHis", $row['published_at'])))->format($this->core->getSettings('blog','dateformat'));
                $keys = array_keys($this->core->lang['blog']);
                $vals = array_values($this->core->lang['blog']);
                $row['published_at'] = str_replace($keys, $vals, strtolower($row['published_at']));

                // generating URLs
                $row['url'] = url('blog/post/'.$row['slug']);

                if(!empty($row['intro']))
                    $row['content'] = $row['intro'];

                if(intval($row['markdown']))
                {
                    if(!isset($parsedown))
                        $parsedown = new \Inc\Core\Lib\Parsedown();
                    $row['content'] = $parsedown->text($row['content']);
                }

                $assign['posts'][$row['id']] = $row;
            }

            $count = $this->core->db('blog')->where('status', 2)->where('published_at', '<=', time())->count();

            if($page > 1)
            {
                $prev['url'] = url('blog/'.($page-1));
                $this->core->tpl->set('prev', $prev);
            }
            if($page < $count/$perpage)
            {
                $next['url'] = url('blog/'.($page+1));
                $this->core->tpl->set('next', $next);
            }
            
            $this->core->template = "blog.html";

            $this->core->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc']]);
            $this->core->tpl->set('blog', $assign);
        }

        /**
        * get array with all posts
        */
        public function _importTagPosts($slug, $page = 1)
        {
            $page = max($page, 1);
            $perpage = $this->core->getSettings('blog', 'perpage');

            if(!($tag = $this->core->db('blog_tags')->oneArray('slug', $slug)))
                return $this->_get404();

            $rows = $this->core->db('blog')->leftJoin('blog_tags_relationship', 'blog_tags_relationship.blog_id = blog.id')->where('blog_tags_relationship.tag_id', $tag['id'])->where('status', 2)->where('published_at', '<=', time())->limit($perpage)->offset(($page-1)*$perpage)->desc('published_at')->toArray();

            $assign = [
                'title' => '#'.$tag['name'],
                'desc' => $this->core->getSettings('blog', 'desc'),
                'posts' => []
            ];
            foreach($rows as $row)
            {
                // get dependences
                $row['author'] = $this->core->db('users')->where('id', $row['user_id'])->oneArray();
                $row['cover_url'] = url('uploads/blog/'.$row['cover_photo']).'?'.$row['published_at'];

                // tags
                $row['tags'] = $this->core->db('blog_tags')
                                    ->leftJoin('blog_tags_relationship', 'blog_tags.id = blog_tags_relationship.tag_id')
                                    ->where('blog_tags_relationship.blog_id', $row['id'])
                                    ->toArray();
                                    
                if($row['tags'])
                {
                    array_walk($row['tags'], function(&$tag) {
                        $tag['url'] = url('blog/tag/'.$tag['slug']);
                    });
                }
                
                // date formatting
                $row['published_at'] = (new \DateTime(date("YmdHis", $row['published_at'])))->format($this->core->getSettings('blog','dateformat'));
                $keys = array_keys($this->core->lang['blog']);
                $vals = array_values($this->core->lang['blog']);
                $row['published_at'] = str_replace($keys, $vals, strtolower($row['published_at']));

                // generating URLs
                $row['url'] = url('blog/post/'.$row['slug']);

                if(!empty($row['intro']))
                    $row['content'] = $row['intro'];

                if(intval($row['markdown']))
                {
                    if(!isset($parsedown))
                        $parsedown = new \Inc\Core\Lib\Parsedown();
                    $row['content'] = $parsedown->text($row['content']);
                }

                $assign['posts'][$row['id']] = $row;
            }

            $count = $this->core->db('blog')->where('status', 2)->where('published_at', '<=', time())->count();

            if($page > 1)
            {
                $prev['url'] = url('blog/tag/'.$slug.'/'.($page-1));
                $this->core->tpl->set('prev', $prev);
            }
            if($page < $count/$perpage)
            {
                $next['url'] = url('blog/tag/'.$slug.'/'.($page+1));
                $this->core->tpl->set('next', $next);
            }
            
            $this->core->template = "blog.html";

            $this->core->tpl->set('page', ['title' => $assign['title'] , 'desc' => $assign['desc']]);
            $this->core->tpl->set('blog', $assign);
        }

        private function _get404()
        {
            $row = $this->core->db('pages')->where('slug', '404')->orWhere('title', '404')->oneArray();
            if(!empty($row)) return $row;
            else return false;
        }

    }