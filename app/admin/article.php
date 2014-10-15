<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2013 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|   
+---------------------------------------------------------------------------
*/

if (! defined('IN_ANWSION'))
{
	die();
}

class article extends AWS_ADMIN_CONTROLLER
{
	public function setup()
	{
		TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(309));
	}
	
	public function list_action()
	{
		if ($this->is_post())
		{			
			foreach ($_POST as $key => $val)
			{
				if ($key == 'start_date' OR $key == 'end_date')
				{
					$val = base64_encode($val);
				}
				
				if ($key == 'keyword' OR $key == 'user_name')
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/article/list/' . implode('__', $param)
			), 1, null));
		}
		
		
		$where = array();
		
		if ($_GET['keyword'])
		{
			$where[] = "(`title` LIKE '%" . $this->model('article')->quote($_GET['keyword']) . "%')";
		}
		
		if ($_GET['start_date'])
		{
			$where[] = 'add_time >= ' . strtotime(base64_decode($_GET['start_date']));
		}
		
		if ($_GET['end_date'])
		{
			$where[] = 'add_time <= ' . strtotime('+1 day', strtotime(base64_decode($_GET['end_date'])));
		}
		
		if ($_GET['user_name'])
		{
			$user_info = $this->model('account')->get_user_info_by_username($_GET['user_name']);
			
			$where[] = 'uid = ' . intval($user_info['uid']);
		}
		
		if ($articles_list = $this->model('article')->fetch_page('article', implode(' AND ', $where), 'id DESC', $_GET['page'], $this->per_page))
		{
			$search_articles_total = $this->model('article')->found_rows();
		}
		
		if ($articles_list)
		{
			foreach ($articles_list AS $key => $val)
			{
				$articles_list_uids[$val['uid']] = $val['uid'];
			}
			
			if ($articles_list_uids)
			{
				$articles_list_user_infos = $this->model('account')->get_user_info_by_uids($articles_list_uids);
			}
			
			foreach ($articles_list AS $key => $val)
			{
				$articles_list[$key]['user_info'] = $articles_list_user_infos[$val['uid']];
			}
		}
			
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if (!in_array($key, array('app', 'c', 'act', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/article/list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url, 
			'total_rows' => $search_articles_total, 
			'per_page' => $this->per_page
		))->create_links());
		
		$this->crumb(AWS_APP::lang()->_t('文章管理'), 'admin/question/question_list/');
		
		TPL::assign('articles_count', $search_articles_total);
		TPL::assign('search_url', $search_url);
		TPL::assign('list', $articles_list);
		
		TPL::output('admin/article/list');
	}
	
	public function batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $_POST['article_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章进行操作')));
		}
		
		foreach ($_POST['article_ids'] AS $key => $article_id)
		{
			$this->model('article')->remove_article($article_id);
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}