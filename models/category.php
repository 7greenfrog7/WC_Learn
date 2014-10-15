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


if (!defined('IN_ANWSION'))
{
	die;
}

class category_class extends AWS_MODEL
{
	public function update_category($category_id, $update_data)
	{
		return $this->update('category', $update_data, 'id = ' . intval($category_id));
	}
	
	public function add_category($type, $title, $parent_id)
	{
		return $this->insert('category', array(
			'type' => $type,
			'title' => $title,
			'parent_id' => intval($parent_id),
		));
	}

	public function delete_category($type, $category_id)
	{
		$childs = $this->model('system')->fetch_category_data($type, $category_id);
		
		if ($childs)
		{
			foreach($childs as $key => $val)
			{
				$this->delete_category($type, $val['id']);
			}
		}
		
		$this->delete('reputation_category', 'category_id = ' . intval($category_id));
		
		$this->delete('nav_menu', "type = 'category' AND type_id = " . intval($category_id));
		
		return $this->delete('category', 'id = ' . intval($category_id));
	}

	public function contents_exists($category_id)
	{
		if ($this->fetch_one('question', 'question_id', 'category_id = ' . intval($category_id)) OR $this->fetch_one('article', 'id', 'category_id = ' . intval($category_id)))
		{
			return true;
		}
	}
	
	public function check_url_token($url_token, $category_id)
	{
		return $this->count('category', "url_token = '" . $this->quote($url_token) . "' AND id != " . intval($category_id));
	}
	
	public function move_contents($from_ids = array(), $target_id)
	{
		if (!is_array($from_ids) OR !$target_id)
		{
			return false;
		}
		
		array_walk_recursive($from_ids, 'intval_string');
		
		$this->update('question', array(
			'category_id' => intval($target_id)
		), 'category_id IN (' . implode(',', $from_ids) .')');
		
		$this->update('article', array(
			'category_id' => intval($target_id)
		), 'category_id IN (' . implode(',', $from_ids) .')');
		
		$this->update('posts_index', array(
			'category_id' => intval($target_id)
		), 'category_id IN (' . implode(',', $from_ids) .')');
	}
}
