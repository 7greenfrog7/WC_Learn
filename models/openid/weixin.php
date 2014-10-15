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

class openid_weixin_class extends AWS_MODEL
{
	public function get_user_info_by_openid_from_mp($openid)
	{
		if ($result = $this->model('wecenter')->mp_server_query('get_user_info', array(
			'openid' => $openid
		)))
		{			
			return $result['data'];
		}
	}
	
	public function get_user_info_by_oauth_openid_from_mp($access_token, $openid)
	{
		if ($result = $this->model('wecenter')->mp_server_query('get_user_info_by_oauth', array(
			'access_token' => $access_token,
			'openid' => $openid
		)))
		{			
			return $result['data'];
		}
	}
	
	public function get_sns_access_token_by_authorization_code($code)
	{
		$cache_process_key = 'processing_weixin_sns_access_token_' . md5($code);
		$cache_key = 'weixin_sns_access_token_' . md5($code);
		
		// 防止页面被二次访问导致 Code 失效
		if (AWS_APP::cache()->get($cache_process_key))
		{
			return $this->get_sns_access_token_by_authorization_code($code);
		}
		
		if ($sns_access_token = AWS_APP::cache()->get($cache_key))
		{
			return $sns_access_token;
		}
		
		AWS_APP::cache()->set($cache_process_key, time(), 60);
		
		if ($result = $this->model('wecenter')->mp_server_query('get_sns_access_token_by_authorization_code', array(
			'code' => $code
		)))
		{
			$sns_access_token = unserialize($result['data']);
			
			if (!$sns_access_token['errcode'])
			{
				AWS_APP::cache()->set($cache_key, $sns_access_token, 60);
			}
		}
		
		AWS_APP::cache()->delete($cache_process_key);
		
		return $sns_access_token;
	}
	
	public function get_user_info_by_openid($open_id)
	{
		return $this->fetch_row('users_weixin', "openid = '" . $this->quote($open_id) . "'");
	}
	
	public function get_user_info_by_uid($uid)
	{
		return $this->fetch_row('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function bind_account($access_user, $access_token, $uid, $is_ajax = false)
	{
		if (! $access_user['nickname'])
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('与微信通信出错, 请重新登录')));
			}
			else
			{
				H::redirect_msg(AWS_APP::lang()->_t('与微信通信出错, 请重新登录'));
			}
		}
		
		if ($openid_info = $this->model('openid_weixin')->get_user_info_by_uid($uid))
		{
			if ($openid_info['opendid'] != $access_user['openid'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('微信账号已经被其他账号绑定')));
				}
				else
				{
					H::redirect_msg(AWS_APP::lang()->_t('微信账号已经被其他账号绑定'));
				}
			}
			
			return true;
		}
		
		$this->model('account')->associate_remote_avatar($uid, $access_user['headimgurl']);
		
		return $this->insert('users_weixin', array(
			'uid' => intval($uid),
			'openid' => $access_token['openid'],
			'expires_in' => (time() + $access_token['expires_in']),
			'access_token' => $access_token['access_token'],
			'refresh_token' => $access_token['refresh_token'],
			'scope' => $access_token['scope'],
			'headimgurl' => $access_user['headimgurl'],
			'nickname' => $access_user['nickname'],
			'sex' => $access_user['sex'],
			'province' => $access_user['province'],
			'city' => $access_user['city'],
			'country' => $access_user['country'],
			'add_time' => time()
		));
	}
	
	public function weixin_unbind($uid)
	{
		return $this->delete('users_weixin', 'uid = ' . intval($uid));
	}
	
	public function get_oauth_url($redirect_uri, $scope = 'snsapi_base', $state = 'STATE')
	{		
		return get_js_url('/m/weixin/oauth_redirect/?uri=' . urlencode($redirect_uri) . '&scope=' . urlencode($scope) . '&state=' . urlencode($state));
	}
	
	public function redirect_url($redirect_uri)
	{
		if (!get_setting('weixin_app_id'))
		{
			return get_js_url($redirect_uri);
		}
		
		return $this->get_oauth_url(get_js_url('/m/weixin/redirect/?redirect=' . base64_encode(get_js_url($redirect_uri))));
	}
	
	public function register_user($access_token, $access_user)
	{
		if (!$access_token OR !$access_user['nickname'])
		{
			return false;
		}
		
		$access_user['nickname'] = str_replace(array(
			'?', '/', '&', '=', '#'
		), '', $access_user['nickname']);
		
		if ($this->model('account')->check_username($access_user['nickname']))
		{
			$access_user['nickname'] .= '_' . rand(1, 999);
		}
		
		if ($uid = $this->model('account')->user_register($access_user['nickname'], md5(rand(111111, 999999999))))
		{
			$this->model('account')->associate_remote_avatar($uid, $access_user['headimgurl']);
			
			$this->model('account')->update('users', array(
				'sex' => intval($access_user['sex'])
			), 'uid = ' . intval($uid));
			
			return $this->model('account')->get_user_info_by_uid($uid);
		}
	}
	
	public function weixin_auto_register($access_token, $access_user)
	{
		if ($user_info = $this->register_user($access_token, $access_user))
		{
			$this->bind_account($access_user, $access_token, $user_info['uid']);
			
			HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], null, false));
			
			return true;
		}
		
		return false;
	}
	
	public function process_client_login($token, $uid)
	{
		return $this->update('weixin_login', array(
			'uid' => intval($uid)
		), "token = '" . intval($token) . "'");
	}
	
	public function request_client_login_token($session_id)
	{
		$this->delete('weixin_login', "session_id = '" . $this->quote($session_id) . "'");
		$this->delete('weixin_login', 'expire <' . time());
		
		$token = rand(11111111, 99999999);
		
		if ($this->fetch_row('weixin_login', "token = " . $token))
		{
			return $this->request_client_login_token($session_id);
		}
		
		$this->insert('weixin_login', array(
			'token' => $token,
			'session_id' => $session_id,
			'expire' => (time() + 300)
		));
		
		return $token;
	}
	
	public function weixin_login_process($session_id)
	{
		$weixin_login = $this->fetch_row('weixin_login', "session_id = '" . $this->quote($session_id) . "' AND expire >= " . time());
		
		if ($weixin_login['uid'])
		{
			$this->delete('weixin_login', "session_id = '" . $this->quote($session_id) . "'");
			
			return $this->model('account')->get_user_info_by_uid($weixin_login['uid']);
		}
	}
}
	